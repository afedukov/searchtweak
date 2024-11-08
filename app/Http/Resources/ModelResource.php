<?php

namespace App\Http\Resources;

use App\Models\SearchModel;
use App\Services\Models\RequestHeadersService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModelResource extends JsonResource
{
    public bool $preserveKeys = true;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SearchModel $model */
        $model = $this->resource;

        return [
            'id' => $model->id,
            'name' => $model->name,
            'description' => $model->description,
            'endpoint' => new EndpointResource($model->endpoint),
            'headers' => $model->headers,
            'params' => $model->params,
            'body' => $model->body,
            'body_type' => RequestHeadersService::getBodyTypes()[$model->body_type] ?? null,
            'settings' => $model->settings,
            'tags' => TagResource::collection($model->tags),
            'created_at' => $model->created_at->toIso8601String(),
        ];
    }
}
