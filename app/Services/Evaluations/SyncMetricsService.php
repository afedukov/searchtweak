<?php

namespace App\Services\Evaluations;

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;

class SyncMetricsService
{
    public function sync(SearchEvaluation $evaluation, array $metrics): void
    {
        $evaluation->refresh();

        foreach ($metrics as $key => $metric) {
            $metrics[$key][EvaluationMetric::FIELD_SETTINGS] ??= [];
        }

        $metrics = collect($metrics);

        // Delete all metrics that are not in the new list
        $metricIdsToDelete = $evaluation->metrics
            ->pluck(EvaluationMetric::FIELD_ID)
            ->diff($metrics->pluck(EvaluationMetric::FIELD_ID));

        EvaluationMetric::destroy($metricIdsToDelete);

        // Create new metrics
        $evaluation->metrics()->createMany(
            $metrics->filter(fn (array $metric) => empty($metric[EvaluationMetric::FIELD_ID]))
        );

        // Update existing metrics
        $metrics->filter(fn (array $metric) => !empty($metric[EvaluationMetric::FIELD_ID]))
            ->each(fn (array $metric) => $evaluation->metrics()->find($metric[EvaluationMetric::FIELD_ID])->update($metric));
    }
}
