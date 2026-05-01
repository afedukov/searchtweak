<?php

namespace App\Jobs\Evaluations;

use App\Events\EvaluationKeywordsChangedEvent;
use App\Jobs\Concerns\ReleasesEvaluationBlock;
use App\Models\SearchEvaluation;
use App\Services\Evaluations\EvaluationExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PostRerunFailedKeywordsJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, ReleasesEvaluationBlock, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $evaluationId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->evaluationId;
    }

    public function handle(EvaluationExecutionService $evaluationExecutionService): void
    {
        $evaluation = SearchEvaluation::find($this->evaluationId);
        if ($evaluation === null) {
            return;
        }

        try {
            $wasPending = $evaluation->isPending();

            if (!$evaluation->isActive() && !$wasPending) {
                return;
            }

            $evaluationExecutionService->syncProcessedKeywordCounts($evaluation);
            $evaluationExecutionService->refreshAggregates($evaluation);
            EvaluationKeywordsChangedEvent::dispatch($evaluation);

            if (!$wasPending) {
                $evaluationExecutionService->dispatchJudgeProcessingIfNeeded($evaluation);
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            throw $e;
        } finally {
            $evaluation->allowChangesAndNotify();
        }
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [sprintf('evaluation:%d', $this->evaluationId)];
    }
}
