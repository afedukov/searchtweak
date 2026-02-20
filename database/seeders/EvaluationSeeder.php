<?php

namespace Database\Seeders;

use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\User;
use App\Services\Evaluations\ScoringGuidelinesService;
use Illuminate\Database\Seeder;

class EvaluationSeeder extends Seeder
{
    /**
     * Seed the application's database with a development search evaluation.
     */
    public function run(): void
    {
        $user = User::where(User::FIELD_EMAIL, 'admin@searchtweak.com')->firstOrFail();
        $model = SearchModel::where(SearchModel::FIELD_NAME, 'Baseline Search')->firstOrFail();

        $evaluation = SearchEvaluation::create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => 'graded',
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING,
            SearchEvaluation::FIELD_PROGRESS => 0,
            SearchEvaluation::FIELD_NAME => 'Baseline Graded Evaluation',
            SearchEvaluation::FIELD_DESCRIPTION => 'Initial graded relevance evaluation for Metro Markets baseline search',
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_NONE,
                SearchEvaluation::SETTING_SHOW_POSITION => false,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
                SearchEvaluation::SETTING_AUTO_RESTART => false,
                SearchEvaluation::SETTING_TRANSFORMERS => [
                    'scale_type' => 'graded',
                    'rules' => [
                        'binary' => [
                            0 => 0,
                            1 => 1,
                            2 => 1,
                            3 => 1,
                        ],
                    ],
                ],
                SearchEvaluation::SETTING_SCORING_GUIDELINES => app(ScoringGuidelinesService::class)->getDefaultScoringGuidelines()['graded'],
            ],
            SearchEvaluation::FIELD_MAX_NUM_RESULTS => null,
            SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => 0,
            SearchEvaluation::FIELD_FAILED_KEYWORDS => 0,
            SearchEvaluation::FIELD_ARCHIVED => false,
            SearchEvaluation::FIELD_PINNED => false,
        ]);

        // Keywords from the search model
        foreach ($model->getKeywords() as $keyword) {
            EvaluationKeyword::create([
                EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
                EvaluationKeyword::FIELD_KEYWORD => $keyword,
            ]);
        }

        $metrics = [
            ['scorer_type' => 'precision', 'num_results' => 10],
            ['scorer_type' => 'cg', 'num_results' => 10],
            ['scorer_type' => 'dcg', 'num_results' => 10],
        ];

        foreach ($metrics as $metric) {
            EvaluationMetric::create([
                EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
                EvaluationMetric::FIELD_SCORER_TYPE => $metric['scorer_type'],
                EvaluationMetric::FIELD_NUM_RESULTS => $metric['num_results'],
                EvaluationMetric::FIELD_VALUE => 0,
                EvaluationMetric::FIELD_PREVIOUS_VALUE => null,
                EvaluationMetric::FIELD_SETTINGS => [],
                EvaluationMetric::FIELD_FINISHED_AT => null,
            ]);
        }
    }
}
