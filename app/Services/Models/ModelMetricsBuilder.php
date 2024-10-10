<?php

namespace App\Services\Models;

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use Illuminate\Support\Collection;

class ModelMetricsBuilder
{
    private const array COLORS = [
        'indigo-500',
        'blue-400',
        'emerald-500',
        'yellow-500',
        'orange-500',
        'pink-500',
        'purple-500',
        'cyan-500',
        'teal-500',
        'lime-500',
        'amber-500',
        'violet-500',
    ];

    /**
     * @param SearchModel $model
     *
     * @return array<ModelMetric>
     */
    public function getMetrics(SearchModel $model): array
    {
        $colors = self::COLORS;

        return $model->evaluations()
            ->where(SearchEvaluation::FIELD_STATUS, SearchEvaluation::STATUS_FINISHED)
            ->with('metrics.evaluation')
            ->get()
            ->flatMap(fn (SearchEvaluation $evaluation) => $evaluation->metrics)
            ->groupBy(fn (EvaluationMetric $metric) => self::getModelMetricId($model->id, $metric->scorer_type, $metric->num_results))
            ->map(function (Collection $metrics, string $key) {
                $metrics = $metrics->sortBy(fn (EvaluationMetric $metric) => $metric->evaluation->finished_at);

                /** @var EvaluationMetric $lastMetric */
                $lastMetric = $metrics->last();
                $scorer = $lastMetric->getScorer();
                $keywordsCount = $lastMetric->evaluation->keywords()->count();

                return (new ModelMetric(
                        id: $key,
                        name: $scorer->getDisplayName($lastMetric->num_results, $keywordsCount),
                        scorerType: $scorer->getType(),
                        briefDescription: $scorer->getBriefDescription($keywordsCount),
                        description: $scorer->getDescription(),
                        scaleType: $scorer->getScale()->getType(),
                        lastMetric: $lastMetric,
                    ))
                    ->setDataset(
                        $metrics->map(fn (EvaluationMetric $metric) => [
                            'label' => $metric->evaluation->finished_at->format('Y-m-d H:i'),
                            'value' => $metric->value,
                        ])
                        ->values()
                        ->all()
                    );
            })
            ->sortByDesc(fn (ModelMetric $metric) => $metric->getLastDatasetItem()['label'])
            ->sortByDesc(fn (ModelMetric $metric) => count($metric->getDataset()))
            ->each(function (ModelMetric $metric) use (&$colors) {
                $metric->setColor(array_shift($colors) ?? 'gray-500');
            })
            ->values()
            ->all();
    }

    public static function getModelMetricId(int $modelId, string $scorerType, int $numResults): string
    {
        return sprintf('model-metric-%d-%s-%d', $modelId, $scorerType, $numResults);
    }
}
