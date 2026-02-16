<?php

namespace Tests\Feature\Services\Models;

use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\User;
use App\Services\Models\ModelMetricsBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelMetricsBuilderTest extends TestCase
{
    use RefreshDatabase;

    private ModelMetricsBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new ModelMetricsBuilder();
    }

    private function createModelWithEvaluation(array $evalOverrides = []): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
        ]);
        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $team->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);

        $evaluation = SearchEvaluation::factory()
            ->finished()
            ->create(array_merge([
                SearchEvaluation::FIELD_USER_ID => $user->id,
                SearchEvaluation::FIELD_MODEL_ID => $model->id,
            ], $evalOverrides));

        // Create a keyword so keywords count > 0
        EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        return [$model, $evaluation, $user];
    }

    public function test_get_metrics_from_finished_evaluation(): void
    {
        [$model, $evaluation] = $this->createModelWithEvaluation();

        EvaluationMetric::factory()->finished(0.85)->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]);

        $metrics = $this->builder->getMetrics($model);

        $this->assertCount(1, $metrics);
        $this->assertEquals('precision', $metrics[0]->getScorerType());
        $this->assertNotEmpty($metrics[0]->getDataset());
        $this->assertNotEmpty($metrics[0]->getColor());
    }

    public function test_excludes_archived_evaluations(): void
    {
        [$model, $evaluation] = $this->createModelWithEvaluation([
            SearchEvaluation::FIELD_ARCHIVED => true,
        ]);

        EvaluationMetric::factory()->finished(0.5)->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]);

        $metrics = $this->builder->getMetrics($model);

        $this->assertEmpty($metrics);
    }

    public function test_excludes_pending_evaluations(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
        ]);
        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $team->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);
        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING,
        ]);

        EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]);

        $metrics = $this->builder->getMetrics($model);

        $this->assertEmpty($metrics);
    }

    public function test_groups_metrics_by_scorer_type_and_num_results(): void
    {
        [$model, $eval1, $user] = $this->createModelWithEvaluation();

        // Second finished evaluation for same model
        $eval2 = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_FINISHED_AT => now()->addHour(),
        ]);
        EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $eval2->id,
        ]);

        EvaluationMetric::factory()->finished(0.5)->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $eval1->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]);
        EvaluationMetric::factory()->finished(0.8)->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $eval2->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]);

        $metrics = $this->builder->getMetrics($model);

        // Both precision@10 metrics should be grouped into one ModelMetric
        $this->assertCount(1, $metrics);
        $this->assertCount(2, $metrics[0]->getDataset());
    }

    public function test_get_model_metric_id(): void
    {
        $id = ModelMetricsBuilder::getModelMetricId(1, 'ndcg', 10);

        $this->assertEquals('model-metric-1-ndcg-10', $id);
    }
}
