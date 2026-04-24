<?php

namespace App\Http\Resources;

use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\KeywordMetric;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class KeywordResource extends JsonResource
{
    public bool $preserveKeys = true;

    /**
     * @param EvaluationKeyword $resource
     * @param Collection<int, EvaluationMetric> $evaluationMetrics
     */
    public function __construct(EvaluationKeyword $resource, private readonly Collection $evaluationMetrics)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EvaluationKeyword $keyword */
        $keyword = $this->resource;

        $keywordMetricsByMetricId = $keyword->keywordMetrics
            ->keyBy(KeywordMetric::FIELD_EVALUATION_METRIC_ID);

        $metrics = $this->evaluationMetrics
            ->map(function (EvaluationMetric $metric) use ($keywordMetricsByMetricId) {
                $value = $keywordMetricsByMetricId->get($metric->id)?->value;

                return [
                    'scorer_type' => $metric->scorer_type,
                    'num_results' => $metric->num_results,
                    'value' => $value === null ? null : floatval(number_format($value, 2)),
                ];
            })
            ->values()
            ->all();

        return [
            'keyword' => $keyword->keyword,
            'metrics' => $metrics,
        ];
    }
}
