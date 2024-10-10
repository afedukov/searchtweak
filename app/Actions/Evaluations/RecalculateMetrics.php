<?php

namespace App\Actions\Evaluations;

use App\Jobs\Evaluations\FinishEvaluationJob;
use App\Models\EvaluationKeyword;
use App\Models\KeywordMetric;
use App\Models\MetricValue;
use App\Models\SearchEvaluation;

readonly class RecalculateMetrics
{
    public function __construct(private FinishSearchEvaluation $finishSearchEvaluation)
    {
    }

    /**
     * @param EvaluationKeyword $keyword
     *
     * @return void
     */
    public function recalculate(EvaluationKeyword $keyword): void
    {
        $keyword->load([
            'evaluation.metrics',
            'snapshots.feedbacks',
        ]);

        $this->recalculateKeywordMetrics($keyword);
        $this->recalculateMetrics($keyword->evaluation);
        $this->updateEvaluation($keyword->evaluation);
    }

    /**
     * @param SearchEvaluation $evaluation
     *
     * @return void
     */
    private function updateEvaluation(SearchEvaluation $evaluation): void
    {
        $evaluation->updateProgress();
        $evaluation->updateTimestamps();
        $evaluation->save();

        if ($evaluation->progress >= 100) {
            FinishEvaluationJob::dispatch($evaluation->id);
        }
    }

    /**
     * @param EvaluationKeyword $keyword
     *
     * @return void
     */
    private function recalculateKeywordMetrics(EvaluationKeyword $keyword): void
    {
        foreach ($keyword->evaluation->metrics as $metric) {
            $value = $metric->calculate($keyword);

            $keyword->keywordMetrics()
                ->updateOrCreate([
                    KeywordMetric::FIELD_EVALUATION_METRIC_ID => $metric->id,
                ], [
                    KeywordMetric::FIELD_VALUE => $value,
                ]);
        }
    }

    /**
     * @param SearchEvaluation $evaluation
     *
     * @return void
     */
    private function recalculateMetrics(SearchEvaluation $evaluation): void
    {
        $evaluation->load('metrics.keywordMetrics');

        foreach ($evaluation->metrics as $metric) {
            $metric->value = $metric->keywordMetrics
                ->whereNotNull(KeywordMetric::FIELD_VALUE)
                ->avg(KeywordMetric::FIELD_VALUE);

            $metric->save();

            if ($metric->value !== null) {
                $metric->values()->create([
                    MetricValue::FIELD_VALUE => $metric->value,
                ]);
            }
        }
    }
}
