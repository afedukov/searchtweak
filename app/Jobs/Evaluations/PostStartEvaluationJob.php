<?php

namespace App\Jobs\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use App\Services\Evaluations\ReuseStrategyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PostStartEvaluationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $evaluationId)
    {
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
        if ($evaluation === null || !$evaluation->isPending()) {
            return;
        }

        try {
            $this->process($evaluation, $reuseStrategyService);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        } finally {
            $evaluation->allowChanges();
        }
    }

    private function process(SearchEvaluation $evaluation, ReuseStrategyService $reuseStrategyService): void
    {
        $this->updateMaxNumResults($evaluation);

        $evaluation->status = SearchEvaluation::STATUS_ACTIVE;
        $evaluation->successful_keywords = $evaluation->keywords()->where(EvaluationKeyword::FIELD_FAILED, false)->count();
        $evaluation->failed_keywords = $evaluation->keywords()->where(EvaluationKeyword::FIELD_FAILED, true)->count();
        $evaluation->save();

        if ($evaluation->getReuseStrategy() !== SearchEvaluation::REUSE_STRATEGY_NONE) {
            $reuseStrategyService->apply($evaluation);
        }
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
