<?php

namespace App\Services\Evaluations;

use App\Events\EvaluationProgressChangedEvent;
use App\Jobs\Evaluations\ProcessJudgeEvaluationJob;
use App\Models\EvaluationKeyword;
use App\Models\Judge;
use App\Models\KeywordMetric;
use App\Models\SearchEvaluation;

class EvaluationExecutionService
{
    public function refreshAggregates(SearchEvaluation $evaluation): void
    {
        $evaluation->load('metrics.keywordMetrics');

        foreach ($evaluation->metrics as $metric) {
            $metric->value = $metric->keywordMetrics
                ->whereNotNull(KeywordMetric::FIELD_VALUE)
                ->avg(KeywordMetric::FIELD_VALUE);

            $metric->save();
        }

        $evaluation->updateProgress();
        $evaluation->save();

        if (!$evaluation->wasChanged(SearchEvaluation::FIELD_PROGRESS)) {
            EvaluationProgressChangedEvent::dispatch($evaluation);
        }
    }

    public function syncProcessedKeywordCounts(SearchEvaluation $evaluation): void
    {
        $evaluation->successful_keywords = $evaluation->keywords()
            ->whereNotNull(EvaluationKeyword::FIELD_EXECUTION_CODE)
            ->where(EvaluationKeyword::FIELD_FAILED, false)
            ->count();

        $evaluation->failed_keywords = $evaluation->keywords()
            ->whereNotNull(EvaluationKeyword::FIELD_EXECUTION_CODE)
            ->where(EvaluationKeyword::FIELD_FAILED, true)
            ->count();

        $evaluation->save();
    }

    public function dispatchJudgeProcessingIfNeeded(SearchEvaluation $evaluation): void
    {
        $teamId = $evaluation->model->team_id;
        $evaluation->loadMissing('tags');

        $hasMatchingJudges = Judge::where(Judge::FIELD_TEAM_ID, $teamId)
            ->active()
            ->with('tags')
            ->get()
            ->contains(fn (Judge $judge) => Judge::matchesEvaluation($judge, $evaluation));

        if ($hasMatchingJudges) {
            ProcessJudgeEvaluationJob::dispatch($evaluation->id);
        }
    }
}
