<?php

namespace App\Http\Resources;

use App\Models\SearchEndpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EndpointResource extends JsonResource
{
    public bool $preserveKeys = true;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SearchEndpoint $endpoint */
        $endpoint = $this->resource;

        return [
            'id' => $endpoint->id,
            'name' => $endpoint->name,
            'method' => $endpoint->method,
            'url' => $endpoint->url,
        ];
    }
}
