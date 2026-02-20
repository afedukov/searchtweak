<?php

namespace App\Jobs\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\Judge;
use App\Models\SearchEvaluation;
use App\Models\SearchSnapshot;
use App\Models\UserFeedback;
use App\Services\Evaluations\UserFeedbackService;
use App\Services\Judges\JudgeHandlerFactory;
use App\Services\Scorers\Scales\ScaleFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessJudgeEvaluationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum wall-clock seconds before re-dispatching to avoid Horizon timeout.
     */
    private const int MAX_RUN_SECONDS = 250;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct(private readonly int $evaluationId)
    {
        $this->onQueue('judges');
    }

    public function uniqueId(): string
    {
        return (string) $this->evaluationId;
    }

    public function handle(JudgeHandlerFactory $handlerFactory): void
    {
        $lock = Cache::lock("judge-eval-{$this->evaluationId}", $this->timeout);

        if (!$lock->get()) {
            return;
        }

        try {
            $this->process($handlerFactory);
        } finally {
            $lock->release();
        }
    }

    private function process(JudgeHandlerFactory $handlerFactory): void
    {
        $evaluation = SearchEvaluation::find($this->evaluationId);
        if ($evaluation === null || !$evaluation->isActive()) {
            return;
        }

        $evaluation->load(['model.team', 'tags', 'keywords']);
        $teamId = $evaluation->model->team_id;
        $scale = ScaleFactory::create($evaluation->scale_type);
        $validGrades = $scale->getGrades();
        $snapshotIds = $this->getEvaluationSnapshotIds($evaluation);

        if ($snapshotIds->isEmpty()) {
            return;
        }

        $startedAt = microtime(true);

        while (true) {
            // Wall-clock budget check — re-dispatch to continue
            if ((microtime(true) - $startedAt) > self::MAX_RUN_SECONDS) {
                self::dispatch($this->evaluationId);
                return;
            }

            // Reload evaluation status
            $evaluation->refresh();
            if (!$evaluation->isActive()) {
                return;
            }

            // Re-fetch matching active judges each cycle (hot-swap support)
            $judges = $this->getMatchingJudges($teamId, $evaluation);
            if ($judges->isEmpty()) {
                return;
            }

            // Check if work remains
            $remainingCount = UserFeedback::query()
                ->whereIn(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshotIds)
                ->availableForJudge()
                ->count();

            if ($remainingCount === 0) {
                // Check if there are human-locked feedbacks whose locks may expire
                if ($this->redispatchIfHumanLockedFeedbacksExist($snapshotIds)) {
                    return;
                }
                return;
            }

            // Round-robin: each judge processes one batch per cycle
            $processedInCycle = 0;
            foreach ($judges as $judge) {
                // Re-check evaluation status
                $evaluation->refresh();
                if (!$evaluation->isActive()) {
                    return;
                }

                try {
                    $processed = $this->processJudgeBatch(
                        $evaluation,
                        $judge,
                        $handlerFactory,
                        $validGrades,
                        $snapshotIds,
                    );
                    $processedInCycle += $processed;
                } catch (\Throwable $e) {
                    Log::error(sprintf(
                        'ProcessJudgeEvaluationJob: judge %d (%s) failed for evaluation %d: %s',
                        $judge->id,
                        $judge->name,
                        $this->evaluationId,
                        $e->getMessage(),
                    ));
                }
            }

            // No progress this cycle — check for human-locked feedbacks before stopping
            if ($processedInCycle === 0) {
                if ($this->redispatchIfHumanLockedFeedbacksExist($snapshotIds)) {
                    return;
                }
                return;
            }
        }
    }

    /**
     * Check for ungraded feedbacks locked by humans and re-dispatch with delay if found.
     *
     * Returns true if re-dispatched (caller should return), false otherwise.
     */
    private function redispatchIfHumanLockedFeedbacksExist(\Illuminate\Support\Collection $snapshotIds): bool
    {
        $humanLockedCount = UserFeedback::query()
            ->whereIn(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshotIds)
            ->whereNull(UserFeedback::FIELD_GRADE)
            ->whereNull(UserFeedback::FIELD_JUDGE_ID)
            ->whereNotNull(UserFeedback::FIELD_USER_ID)
            ->where(UserFeedback::FIELD_UPDATED_AT, '>=', now()->subMinutes(UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES))
            ->count();

        if ($humanLockedCount > 0) {
            $delayMinutes = UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES + 1;

            self::dispatch($this->evaluationId)->delay(now()->addMinutes($delayMinutes));

            return true;
        }

        return false;
    }

    /**
     * Get all snapshot IDs for this evaluation.
     */
    private function getEvaluationSnapshotIds(SearchEvaluation $evaluation): \Illuminate\Support\Collection
    {
        $keywordIds = $evaluation->keywords->pluck(EvaluationKeyword::FIELD_ID);

        return SearchSnapshot::query()
            ->whereIn(SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID, $keywordIds)
            ->pluck(SearchSnapshot::FIELD_ID);
    }

    /**
     * Get active judges for this team that match the evaluation's tags.
     */
    private function getMatchingJudges(int $teamId, SearchEvaluation $evaluation): Collection
    {
        return Judge::where(Judge::FIELD_TEAM_ID, $teamId)
            ->active()
            ->with('tags')
            ->get()
            ->filter(fn (Judge $judge) => Judge::matchesEvaluation($judge, $evaluation))
            ->values();
    }

    /**
     * Claim a batch, call LLM, and record grades. Returns number of feedbacks graded.
     */
    private function processJudgeBatch(
        SearchEvaluation $evaluation,
        Judge $judge,
        JudgeHandlerFactory $handlerFactory,
        array $validGrades,
        \Illuminate\Support\Collection $snapshotIds,
    ): int {
        $batchSize = $judge->getBatchSize();

        // Claim feedbacks atomically
        $claimed = $this->claimFeedbacks($snapshotIds, $judge, $batchSize);
        if ($claimed->isEmpty()) {
            return 0;
        }

        // Build pairs payload for the prompt
        $pairs = $this->buildPairs($claimed);

        // Create a single handler instance for both buildPrompt and grade
        $handler = $handlerFactory->create($judge);

        $prompt = $handler->buildPrompt(
            $judge->getPromptForScale($evaluation->scale_type),
            $pairs,
        );

        try {
            $results = $handler
                ->withContext($this->evaluationId, count($pairs), $evaluation->scale_type)
                ->grade($judge, $prompt, $validGrades);
        } catch (\Throwable $e) {
            $this->releaseClaimed($claimed);
            return 0;
        }

        // Apply grades
        $gradedCount = 0;
        foreach ($claimed as $index => $feedback) {
            $result = $results[$index] ?? null;
            if ($result === null) {
                // LLM did not return a result for this pair — release slot
                $feedback->update([UserFeedback::FIELD_JUDGE_ID => null]);
                continue;
            }

            $feedback->user_id = null;
            $feedback->grade = $result['grade'];
            $feedback->reason = $result['reason'];
            $feedback->save(); // triggers RecalculateMetricsJob via booted()
            $gradedCount++;
        }

        return $gradedCount;
    }

    /**
     * Claim available feedbacks for a judge using DB-level locking.
     *
     * @return Collection<int, UserFeedback> Indexed by pair_index (0-based)
     */
    private function claimFeedbacks(
        \Illuminate\Support\Collection $snapshotIds,
        Judge $judge,
        int $batchSize,
    ): Collection {
        $claimed = new Collection();

        DB::transaction(function () use ($snapshotIds, $judge, $batchSize, &$claimed) {
            $claimedSnapshotIds = [];

            for ($i = 0; $i < $batchSize; $i++) {
                /** @var UserFeedback $feedback */
                $feedback = UserFeedback::query()
                    ->whereIn(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshotIds)
                    ->availableForJudge()
                    // Prevent claiming another slot of the same snapshot in this batch.
                    ->when(!empty($claimedSnapshotIds), fn ($query) =>
                        $query->whereNotIn(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $claimedSnapshotIds)
                    )
                    // One judge can grade at most one slot per query/doc pair (snapshot) across the whole evaluation.
                    ->whereNotExists(function ($query) use ($judge) {
                        $query->selectRaw('1')
                            ->from('user_feedbacks as judged_feedbacks')
                            ->whereColumn(
                                'judged_feedbacks.' . UserFeedback::FIELD_SEARCH_SNAPSHOT_ID,
                                'user_feedbacks.' . UserFeedback::FIELD_SEARCH_SNAPSHOT_ID
                            )
                            ->where('judged_feedbacks.' . UserFeedback::FIELD_JUDGE_ID, $judge->id);
                    })
                    ->lockForUpdate()
                    ->first();

                if ($feedback === null) {
                    break;
                }

                $feedback->judge_id = $judge->id;
                $feedback->save();

                $claimed->push($feedback);
                $claimedSnapshotIds[] = $feedback->search_snapshot_id;
            }
        });

        return $claimed;
    }

    /**
     * Release feedbacks back to the pool on LLM failure.
     */
    private function releaseClaimed(Collection $claimed): void
    {
        $ids = $claimed->pluck(UserFeedback::FIELD_ID)->all();
        if (!empty($ids)) {
            UserFeedback::query()
                ->whereIn(UserFeedback::FIELD_ID, $ids)
                ->update([UserFeedback::FIELD_JUDGE_ID => null]);
        }
    }

    /**
     * Build the pairs array for the LLM prompt.
     */
    private function buildPairs(Collection $feedbacks): array
    {
        $feedbacks->load('snapshot.keyword');

        return $feedbacks->values()->map(function (UserFeedback $feedback, int $index) {
            $snapshot = $feedback->snapshot;
            return [
                'pair_index' => $index,
                'query' => $snapshot->keyword->keyword,
                'doc_id' => $snapshot->doc_id,
                'name' => $snapshot->name,
                'position' => $snapshot->position,
                'doc' => $snapshot->doc,
            ];
        })->all();
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [sprintf('evaluation:%d', $this->evaluationId)];
    }
}
