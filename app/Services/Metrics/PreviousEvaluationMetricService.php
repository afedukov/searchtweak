<?php

namespace App\Services\Metrics;

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use Illuminate\Database\Eloquent\Builder;

class PreviousEvaluationMetricService
{
    /**
     * Get the previous finished non-archived evaluation metric for the given metric of the same
     * model and scorer type.
     *
     * @param EvaluationMetric $metric
     *
     * @return EvaluationMetric|null
     */
    public function getPrevious(EvaluationMetric $metric): ?EvaluationMetric
    {
        return EvaluationMetric::query()
            ->where(EvaluationMetric::FIELD_SCORER_TYPE, $metric->scorer_type)
            ->where(EvaluationMetric::FIELD_NUM_RESULTS, $metric->num_results)
            ->where(EvaluationMetric::FIELD_SEARCH_EVALUATION_ID, '!=', $metric->search_evaluation_id)
            ->whereNotNull(EvaluationMetric::FIELD_FINISHED_AT)
            ->where(EvaluationMetric::FIELD_FINISHED_AT, '<=', $metric->created_at)
            ->whereHas('evaluation', fn (Builder $query) =>
                $query->where(SearchEvaluation::FIELD_MODEL_ID, $metric->evaluation->model_id)
                    ->where(SearchEvaluation::FIELD_STATUS, SearchEvaluation::STATUS_FINISHED)
                    ->where(SearchEvaluation::FIELD_ARCHIVED, false)
            )
            ->orderByDesc(EvaluationMetric::FIELD_FINISHED_AT)
            ->first();
    }

    /**
     * Update the previous values of all metrics of evaluations of the given model.
     *
     * @param int $modelId
     * @param int $evaluationId
     *
     * @return void
     */
    public function updatePreviousValues(int $modelId, int $evaluationId): void
    {
        /*
         * Loop through all evaluations of the given model, then loop through all metrics of
         * each evaluation and sync their previous values.
         */
        SearchEvaluation::query()
            ->with('metrics.evaluation')
            ->where(SearchEvaluation::FIELD_MODEL_ID, $modelId)
            ->where(SearchEvaluation::FIELD_ID, '!=', $evaluationId)
            ->orderBy(SearchEvaluation::FIELD_ID)
            ->get()
            ->flatMap(fn (SearchEvaluation $evaluation) => $evaluation->metrics)
            ->each(fn (EvaluationMetric $metric) => $metric->syncPreviousValue());
    }
}
