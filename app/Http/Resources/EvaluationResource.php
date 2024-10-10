<?php

namespace App\Http\Resources;

use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SearchEvaluation $evaluation */
        $evaluation = $this->resource;

        return [
            'id' => $evaluation->id,
            'model_id' => $evaluation->model_id,
            'scale_type' => $evaluation->scale_type,
            'status' => strtolower($evaluation->status_label),
            'progress' => floatval(number_format($evaluation->progress, 2)),
            'name' => $evaluation->name,
            'description' => $evaluation->description,
            'metrics' => MetricResource::collection($evaluation->metrics),
            'tags' => TagResource::collection($evaluation->tags),
            'keywords' => $evaluation->keywords->pluck(EvaluationKeyword::FIELD_KEYWORD)->all(),
            'created_at' => $evaluation->created_at->toIso8601String(),
            'finished_at' => $evaluation->finished_at?->toIso8601String(),
        ];
    }
}
