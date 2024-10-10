<?php

namespace App\Actions\Evaluations;

use App\Jobs\Evaluations\StartEvaluationJob;
use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use App\Services\Evaluations\SyncKeywordsService;
use App\Services\Evaluations\SyncMetricsService;
use App\Services\SyncTagsService;

class AutoRestartSearchEvaluation
{
    public function restart(SearchEvaluation $evaluation): void
    {
        $newEvaluation = new SearchEvaluation();

        $newEvaluation->user_id = $evaluation->user_id;
        $newEvaluation->model_id = $evaluation->model_id;
        $newEvaluation->scale_type = $evaluation->scale_type;
        $newEvaluation->status = SearchEvaluation::STATUS_PENDING;
        $newEvaluation->progress = 0;
        $newEvaluation->name = self::generateNewEvaluationName($evaluation->name);
        $newEvaluation->description = $evaluation->description;
        $newEvaluation->settings = $evaluation->settings;
        $newEvaluation->max_num_results = $evaluation->max_num_results;
        $newEvaluation->finished_at = null;

        $newEvaluation->save();

        $metrics = $evaluation->metrics
            ->map(fn (EvaluationMetric $metric) => $metric->only([
                EvaluationMetric::FIELD_SCORER_TYPE,
                EvaluationMetric::FIELD_NUM_RESULTS,
                EvaluationMetric::FIELD_SETTINGS,
            ]))
            ->all();

        app(SyncKeywordsService::class)->syncArray($newEvaluation, $evaluation->keywords->pluck(EvaluationKeyword::FIELD_KEYWORD)->all());
        app(SyncMetricsService::class)->sync($newEvaluation, $metrics);
        app(SyncTagsService::class)->syncTags($newEvaluation, $evaluation->tags->toArray());

        $newEvaluation->blockChanges();

        StartEvaluationJob::dispatch($newEvaluation->id);
    }

    private static function generateNewEvaluationName(string $lastName): string
    {
        if (preg_match('/^(.*)([\s_\-])(\d+)$/', $lastName, $matches)) {
            return sprintf('%s%s%d', $matches[1], $matches[2], intval($matches[3]) + 1);
        }

        return sprintf('%s %d', $lastName, 1);
    }
}
