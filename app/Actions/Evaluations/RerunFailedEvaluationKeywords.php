<?php

namespace App\Actions\Evaluations;

use App\Exceptions\CannotRerunFailedKeywordsException;
use App\Jobs\Evaluations\PostRerunFailedKeywordsJob;
use App\Jobs\Evaluations\ProcessKeywordJob;
use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;

readonly class RerunFailedEvaluationKeywords
{
    /**
     * @throws Throwable
     */
    public function rerun(SearchEvaluation $evaluation): void
    {
        $this->validate($evaluation);

        $failedKeywordIds = $this->getFailedKeywordIds($evaluation);

        $this->dispatchJobsBatch($evaluation, $failedKeywordIds);
    }

    private function validate(SearchEvaluation $evaluation): void
    {
        if (!$evaluation->hasStarted()) {
            throw new CannotRerunFailedKeywordsException('Failed to rerun failed keywords: evaluation has not started');
        }

        if (!$evaluation->isActive() && !$evaluation->isPending()) {
            throw new CannotRerunFailedKeywordsException('Failed to rerun failed keywords: evaluation must be active or pending');
        }

        if (!$evaluation->keywords()->where(EvaluationKeyword::FIELD_FAILED, true)->exists()) {
            throw new CannotRerunFailedKeywordsException('Failed to rerun failed keywords: evaluation has no failed keywords');
        }
    }

    /**
     * @return array<int>
     */
    private function getFailedKeywordIds(SearchEvaluation $evaluation): array
    {
        return $evaluation->keywords()
            ->where(EvaluationKeyword::FIELD_FAILED, true)
            ->pluck(EvaluationKeyword::FIELD_ID)
            ->all();
    }

    /**
     * @param array<int> $failedKeywordIds
     *
     * @throws Throwable
     */
    private function dispatchJobsBatch(SearchEvaluation $evaluation, array $failedKeywordIds): void
    {
        $jobs = array_map(
            fn (int $keywordId) => new ProcessKeywordJob($keywordId, resetBeforeProcessing: true),
            $failedKeywordIds,
        );

        if ($jobs === []) {
            PostRerunFailedKeywordsJob::dispatch($evaluation->id);

            return;
        }

        Bus::batch($jobs)
            ->name(sprintf('Rerun Failed Keywords %d', $evaluation->id))
            ->allowFailures()
            ->onQueue($evaluation->model->endpoint->getExecutionQueue())
            ->finally(fn (Batch $batch) => PostRerunFailedKeywordsJob::dispatch($evaluation->id))
            ->dispatch();
    }
}
