<?php

namespace App\Http\Resources;

use App\Models\EvaluationMetric;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetricResource extends JsonResource
{
    public bool $preserveKeys = true;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EvaluationMetric $metric */
        $metric = $this->resource;

        return [
            'scorer_type' => $metric->scorer_type,
            'num_results' => $metric->num_results,
            'value' => $metric->value === null ? null : floatval(number_format($metric->value, 2)),
        ];
    }
}
