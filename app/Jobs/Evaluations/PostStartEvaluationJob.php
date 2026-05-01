<?php

namespace App\Jobs\Evaluations;

use App\Jobs\Concerns\ReleasesEvaluationBlock;
use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\Judge;
use App\Models\SearchEvaluation;
use App\Services\Evaluations\ReuseStrategyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostStartEvaluationJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, ReleasesEvaluationBlock, SerializesModels;

    public int $tries = 3;

    public int $timeout = 500;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $evaluationId)
    {
        $this->onQueue('evaluations-heavy');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->evaluationId;
    }

    /**
     * Execute the job.
     */
    public function handle(ReuseStrategyService $reuseStrategyService): void
    {
        $evaluation = SearchEvaluation::find($this->evaluationId);
        if ($evaluation === null) {
            return;
        }

        try {
            // The early-return must live inside the try so that `finally` always
            // runs `allowChanges()` — otherwise a retry that arrives after the
            // status has already moved past pending would leave the UI spinner
            // stuck until the 15-min cache TTL.
            if (!$evaluation->isPending()) {
                return;
            }

            $this->process($evaluation, $reuseStrategyService);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            // Re-throw so the queue worker records the attempt as failed and
            // schedules a retry. Swallowing the error would leave the eval in
            // a half-processed state with `dispatchJudgeProcessingIfNeeded()`
            // skipped and no automatic recovery.
            throw $e;
        } finally {
            $evaluation->allowChangesAndNotify();
        }
    }

    private function process(SearchEvaluation $evaluation, ReuseStrategyService $reuseStrategyService): void
    {
        $this->updateMaxNumResults($evaluation);

        if ($evaluation->getReuseStrategy() !== SearchEvaluation::REUSE_STRATEGY_NONE) {
            $reuseStrategyService->apply($evaluation);
        }

        $shouldDispatchJudge = $this->hasMatchingActiveJudge($evaluation);

        // Save and judge dispatch must be atomic: if either throws, status stays
        // PENDING so the retry can re-enter (the `isPending()` guard above
        // otherwise short-circuits). ShouldQueueAfterCommit on the judge job
        // defers the Redis push until commit, preventing the judge worker from
        // racing the save and seeing status=PENDING.
        DB::transaction(function () use ($evaluation, $shouldDispatchJudge) {
            $evaluation->status = SearchEvaluation::STATUS_ACTIVE;
            $evaluation->successful_keywords = $evaluation->keywords()->where(EvaluationKeyword::FIELD_FAILED, false)->count();
            $evaluation->failed_keywords = $evaluation->keywords()->where(EvaluationKeyword::FIELD_FAILED, true)->count();
            $evaluation->save();

            if ($shouldDispatchJudge) {
                ProcessJudgeEvaluationJob::dispatch($this->evaluationId);
            }
        });
    }

    private function hasMatchingActiveJudge(SearchEvaluation $evaluation): bool
    {
        $evaluation->loadMissing('tags');

        return Judge::where(Judge::FIELD_TEAM_ID, $evaluation->model->team_id)
            ->active()
            ->with('tags')
            ->get()
            ->contains(fn (Judge $judge) => Judge::matchesEvaluation($judge, $evaluation));
    }

    private function updateMaxNumResults(SearchEvaluation $evaluation): void
    {
        if ($evaluation->max_num_results !== null) {
            return;
        }

        $evaluation->max_num_results = $evaluation->metrics->max(EvaluationMetric::FIELD_NUM_RESULTS);
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
