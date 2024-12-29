<?php

namespace App\Http\Controllers\Api;

use App\Actions\Evaluations\DeleteSearchEvaluation;
use App\Actions\Evaluations\FinishSearchEvaluation;
use App\Http\Requests\EvaluationsRequest;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\Jobs\Evaluations\PauseEvaluationJob;
use App\Jobs\Evaluations\StartEvaluationJob;
use App\Models\SearchEvaluation;
use App\Services\Evaluations\JudgementsService;
use App\Services\Evaluations\ScoringGuidelinesService;
use App\Services\Evaluations\SyncKeywordsService;
use App\Services\Evaluations\SyncMetricsService;
use App\Services\SyncTagsService;
use App\Services\Transformers\Transformers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EvaluationsController
{
    /**
     * GET /api/v1/evaluations
     *
     * @param EvaluationsRequest $request
     *
     * @return ResourceCollection
     */
    public function index(EvaluationsRequest $request): ResourceCollection
    {
        $team = Auth::guard('api')->user();

        $evaluations = SearchEvaluation::team($team->id)
            ->with('metrics')
            ->when($request->has('model_id'), fn (Builder $query) =>
                $query->where(SearchEvaluation::FIELD_MODEL_ID, $request->get('model_id'))
            )
            ->when($request->has('status'), fn (Builder $query) =>
                $query->where(SearchEvaluation::FIELD_STATUS, SearchEvaluation::getStatusByLabel($request->get('status')))
            )
            ->when($request->has('scale_type'), fn (Builder $query) =>
                $query->where(SearchEvaluation::FIELD_SCALE_TYPE, $request->get('scale_type'))
            )
            ->orderByDesc(SearchEvaluation::FIELD_ID)
            ->get();

        return EvaluationResource::collection($evaluations);
    }

    /**
     * GET /api/v1/evaluations/{id}
     *
     * @param int $id
     *
     * @return EvaluationResource
     */
    public function show(int $id): EvaluationResource
    {
        $evaluation = $this->getEvaluation($id)
            ->load('metrics');

        return new EvaluationResource($evaluation);
    }

    /**
     * POST /api/v1/evaluations/{id}/start
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function start(int $id): JsonResponse
    {
        $evaluation = $this->getEvaluation($id);

        if ($evaluation->changes_blocked) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Evaluation changes are blocked');
        }

        if (!$evaluation->isPending()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Evaluation is not pending');
        }

        $evaluation->blockChanges();

        StartEvaluationJob::dispatch($evaluation->id);

        return response()->json([
            'status' => 'OK',
            'message' => 'Evaluation start job dispatched',
        ]);
    }

    /**
     * POST /api/v1/evaluations/{id}/stop
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function stop(int $id): JsonResponse
    {
        $evaluation = $this->getEvaluation($id);

        if ($evaluation->changes_blocked) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Evaluation changes are blocked');
        }

        if (!$evaluation->isActive()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Evaluation is not active');
        }

        $evaluation->blockChanges();

        PauseEvaluationJob::dispatch($evaluation->id);

        return response()->json([
            'status' => 'OK',
            'message' => 'Evaluation stop job dispatched',
        ]);
    }

    /**
     * POST /api/v1/evaluations/{id}/finish
     *
     * @param int $id
     * @param FinishSearchEvaluation $action
     *
     * @return JsonResponse
     */
    public function finish(int $id, FinishSearchEvaluation $action): JsonResponse
    {
        $evaluation = $this->getEvaluation($id);

        try {
            $action->finish($evaluation, false);
        } catch (\Exception $e) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
        }

        return response()->json([
            'status' => 'OK',
            'message' => 'Evaluation finished',
        ]);
    }

    public function delete(int $id, DeleteSearchEvaluation $action): JsonResponse
    {
        $evaluation = $this->getEvaluation($id);

        try {
            $action->delete($evaluation);
        } catch (\Exception $e) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
        }

        return response()->json([
            'status' => 'OK',
            'message' => 'Evaluation deleted',
        ]);
    }

    /**
     * GET /api/v1/evaluations/{id}/judgements
     *
     * @param int $id
     * @param JudgementsService $judgementsService
     *
     * @return JsonResponse
     */
    public function judgements(int $id, JudgementsService $judgementsService): JsonResponse
    {
        $evaluation = $this->getEvaluation($id)
            ->load('keywords.snapshots.feedbacks');

        try {
            if (!$evaluation->isFinished()) {
                throw new \RuntimeException('Evaluation is not finished');
            }

            $judgements = [];

            $judgementsService->process($evaluation, function ($grade, $keyword, $doc, $position) use (&$judgements) {
                $judgements[] = [
                    'grade' => $grade,
                    'keyword' => $keyword,
                    'position' => $position,
                    'doc' => $doc,
                ];
            });
        } catch (\Exception $e) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
        }

        return response()->json($judgements);
    }

    /**
     * POST /api/v1/evaluations
     *
     * @param StoreEvaluationRequest $request
     *
     * @return EvaluationResource
     */
    public function store(StoreEvaluationRequest $request): EvaluationResource
    {
        $team = Auth::guard('api')->user();

        $attributes = [
                SearchEvaluation::FIELD_USER_ID => $team->user_id,
                SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING,
                SearchEvaluation::FIELD_SETTINGS => [
                    SearchEvaluation::SETTING_FEEDBACK_STRATEGY => $request->get('setting_feedback_strategy'),
                    SearchEvaluation::SETTING_SHOW_POSITION => $request->get('setting_show_position'),
                    SearchEvaluation::SETTING_REUSE_STRATEGY => $request->get('setting_reuse_strategy'),
                    SearchEvaluation::SETTING_AUTO_RESTART => $request->get('setting_auto_restart'),
                    SearchEvaluation::SETTING_TRANSFORMERS => Transformers::fromArray($request->get('transformers'))->toArray(),
                    SearchEvaluation::SETTING_SCORING_GUIDELINES => app(ScoringGuidelinesService::class)
                        ->prepareScoringGuidelinesForSave((string) $request->get('setting_scoring_guidelines')),
                ],
            ] + $request->validated() + [
                SearchEvaluation::FIELD_DESCRIPTION => '',
            ];

        $evaluation = SearchEvaluation::create($attributes);

        app(SyncKeywordsService::class)->syncArray($evaluation, $request->get('keywords'));
        app(SyncMetricsService::class)->sync($evaluation, $request->get('metrics'));
        app(SyncTagsService::class)->syncTags($evaluation, $request->get('tags') ?? []);

        return new EvaluationResource($evaluation->load('metrics'));
    }

    /**
     * @param int $id
     *
     * @return SearchEvaluation
     */
    private function getEvaluation(int $id): SearchEvaluation
    {
        $team = Auth::guard('api')->user();

        $evaluation = SearchEvaluation::team($team->id)
            ->find($id);

        if ($evaluation === null) {
            throw new ModelNotFoundException('Not found');
        }

        return $evaluation;
    }
}
