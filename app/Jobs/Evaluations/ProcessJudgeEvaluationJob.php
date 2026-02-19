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
            Log::channel('judges')->info(sprintf(
                'ProcessJudgeEvaluationJob[%d]: exiting — another instance already processing',
                $this->evaluationId,
            ));
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
            Log::channel('judges')->info(sprintf(
                'ProcessJudgeEvaluationJob[%d]: exiting — evaluation %s',
                $this->evaluationId,
                $evaluation === null ? 'not found' : 'not active (status=' . $evaluation->status . ')',
            ));
            return;
        }

        $evaluation->load(['model.team', 'tags', 'keywords']);
        $teamId = $evaluation->model->team_id;
        $scale = ScaleFactory::create($evaluation->scale_type);
        $validGrades = $scale->getGrades();
        $snapshotIds = $this->getEvaluationSnapshotIds($evaluation);

        if ($snapshotIds->isEmpty()) {
            Log::channel('judges')->info(sprintf(
                'ProcessJudgeEvaluationJob[%d]: exiting — no snapshots found',
                $this->evaluationId,
            ));
            return;
        }

        Log::channel('judges')->info(sprintf(
            'ProcessJudgeEvaluationJob[%d]: starting — %d snapshots, scale=%s, validGrades=[%s]',
            $this->evaluationId,
            $snapshotIds->count(),
            $evaluation->scale_type,
            implode(',', $validGrades),
        ));

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
                Log::channel('judges')->info(sprintf(
                    'ProcessJudgeEvaluationJob[%d]: exiting — no matching judges',
                    $this->evaluationId,
                ));
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

                Log::channel('judges')->info(sprintf(
                    'ProcessJudgeEvaluationJob[%d]: exiting — no feedbacks available for judging',
                    $this->evaluationId,
                ));
                return;
            }

            Log::channel('judges')->info(sprintf(
                'ProcessJudgeEvaluationJob[%d]: cycle start — %d remaining, %d judges [%s]',
                $this->evaluationId,
                $remainingCount,
                $judges->count(),
                $judges->pluck('name')->join(', '),
            ));

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

                Log::channel('judges')->info(sprintf(
                    'ProcessJudgeEvaluationJob[%d]: exiting — no progress in cycle',
                    $this->evaluationId,
                ));
                return;
            }

            Log::channel('judges')->info(sprintf(
                'ProcessJudgeEvaluationJob[%d]: cycle complete — %d graded',
                $this->evaluationId,
                $processedInCycle,
            ));
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

            Log::channel('judges')->info(sprintf(
                'ProcessJudgeEvaluationJob[%d]: %d feedbacks locked by humans — re-dispatching in %d minutes',
                $this->evaluationId,
                $humanLockedCount,
                $delayMinutes,
            ));

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
            Log::channel('judges')->debug(sprintf(
                'ProcessJudgeEvaluationJob[%d]: judge "%s" (id=%d) — no feedbacks to claim',
                $this->evaluationId,
                $judge->name,
                $judge->id,
            ));
            return 0;
        }

        Log::channel('judges')->info(sprintf(
            'ProcessJudgeEvaluationJob[%d]: judge "%s" (id=%d) — claimed %d feedbacks (batch_size=%d)',
            $this->evaluationId,
            $judge->name,
            $judge->id,
            $claimed->count(),
            $batchSize,
        ));

        // Build pairs payload for the prompt
        $pairs = $this->buildPairs($claimed);

        // Create a single handler instance for both buildPrompt and grade
        $handler = $handlerFactory->create($judge);

        $prompt = $handler->buildPrompt(
            $judge->getPromptForScale($evaluation->scale_type),
            $pairs,
        );

        Log::channel('judges')->info(sprintf(
            'ProcessJudgeEvaluationJob[%d]: judge "%s" — sending prompt (%d chars) to %s/%s',
            $this->evaluationId,
            $judge->name,
            strlen($prompt),
            $judge->provider,
            $judge->model_name,
        ));
        Log::channel('judges')->debug(sprintf(
            "ProcessJudgeEvaluationJob[%d]: === PROMPT START ===\n%s\n=== PROMPT END ===",
            $this->evaluationId,
            $prompt,
        ));

        try {
            $results = $handler
                ->withContext($this->evaluationId, count($pairs), $evaluation->scale_type)
                ->grade($judge, $prompt, $validGrades);
        } catch (\Throwable $e) {
            Log::channel('judges')->error(sprintf(
                'ProcessJudgeEvaluationJob[%d]: LLM API error for judge "%s" (id=%d): %s',
                $this->evaluationId,
                $judge->name,
                $judge->id,
                $e->getMessage(),
            ));
            $this->releaseClaimed($claimed);
            return 0;
        }

        Log::channel('judges')->info(sprintf(
            'ProcessJudgeEvaluationJob[%d]: judge "%s" — received %d results',
            $this->evaluationId,
            $judge->name,
            count($results),
        ));
        Log::channel('judges')->debug(sprintf(
            "ProcessJudgeEvaluationJob[%d]: === RESPONSE ===\n%s",
            $this->evaluationId,
            json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ));

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

        Log::channel('judges')->info(sprintf(
            'ProcessJudgeEvaluationJob[%d]: judge "%s" — graded %d/%d feedbacks',
            $this->evaluationId,
            $judge->name,
            $gradedCount,
            $claimed->count(),
        ));

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
        $claimed = collect();

        DB::transaction(function () use ($snapshotIds, $judge, $batchSize, &$claimed) {
            $feedbacks = UserFeedback::query()
                ->whereIn(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshotIds)
                ->availableForJudge()
                ->lockForUpdate()
                ->limit($batchSize)
                ->get();

            if ($feedbacks->isEmpty()) {
                return;
            }

            // Claim all at once
            UserFeedback::query()
                ->whereIn(UserFeedback::FIELD_ID, $feedbacks->pluck(UserFeedback::FIELD_ID))
                ->update([UserFeedback::FIELD_JUDGE_ID => $judge->id]);

            $claimed = $feedbacks->values()->each(function (UserFeedback $f) use ($judge) {
                $f->judge_id = $judge->id;
            });
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
