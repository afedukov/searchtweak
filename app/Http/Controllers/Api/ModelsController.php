<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ModelResource;
use App\Models\SearchModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;

class ModelsController
{
    /**
     * GET /api/v1/models
     *
     * @return ResourceCollection
     */
    public function index(): ResourceCollection
    {
        $team = Auth::guard('api')->user();

        $models = SearchModel::query()
            ->where(SearchModel::FIELD_TEAM_ID, $team->id)
            ->orderByDesc(SearchModel::FIELD_ID)
            ->get();

        return ModelResource::collection($models);
    }

    /**
     * GET /api/v1/models/{id}
     *
     * @param int $id
     *
     * @return ModelResource
     */
    public function show(int $id): ModelResource
    {
        return new ModelResource($this->getModel($id));
    }

    /**
     * @param int $id
     *
     * @return SearchModel
     */
    private function getModel(int $id): SearchModel
    {
        $team = Auth::guard('api')->user();

        $model = SearchModel::query()
            ->where(SearchModel::FIELD_TEAM_ID, $team->id)
            ->find($id);

        if ($model === null) {
            throw new ModelNotFoundException('Not found');
        }

        return $model;
    }
}
