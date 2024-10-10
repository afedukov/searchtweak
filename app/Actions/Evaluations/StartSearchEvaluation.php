<?php

namespace App\Actions\Evaluations;

use App\Jobs\Evaluations\PostStartEvaluationJob;
use App\Jobs\Evaluations\ProcessKeywordJob;
use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;

readonly class StartSearchEvaluation
{
    /**
     * @throws Throwable
     */
    public function start(SearchEvaluation $evaluation): void
    {
        $this->validate($evaluation);
        $this->dispatchJobsBatch($evaluation);
    }

    private function validate(SearchEvaluation $evaluation): void
    {
        if (!$evaluation->isPending()) {
            throw new \RuntimeException('Failed to start evaluation: evaluation is not pending');
        }
    }

    /**
     * @throws Throwable
     */
    private function dispatchJobsBatch(SearchEvaluation $evaluation): void
    {
        $jobs = [];

        $keywords = $evaluation->keywords
            ->whereNull(EvaluationKeyword::FIELD_EXECUTION_CODE);

        foreach ($keywords as $keyword) {
            $jobs[] = new ProcessKeywordJob($keyword->id);
        }

        if ($jobs) {
            Bus::batch($jobs)
                ->name(sprintf('Start Evaluation %d', $evaluation->id))
                ->allowFailures()
                ->onQueue($evaluation->model->endpoint->getExecutionQueue())
                ->finally(fn (Batch $batch) => PostStartEvaluationJob::dispatch($evaluation->id))
                ->dispatch();
        } else {
            PostStartEvaluationJob::dispatch($evaluation->id);
        }
    }
}
