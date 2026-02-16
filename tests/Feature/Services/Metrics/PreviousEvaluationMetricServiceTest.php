<?php

namespace Tests\Feature\Services\Metrics;

use App\Models\EvaluationMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\User;
use App\Services\Metrics\PreviousEvaluationMetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviousEvaluationMetricServiceTest extends TestCase
{
    use RefreshDatabase;

    private PreviousEvaluationMetricService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PreviousEvaluationMetricService();
    }

    private function createModelAndUser(): array
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

        return [$model, $user];
    }

    public function test_find_previous_metric(): void
    {
        [$model, $user] = $this->createModelAndUser();

        $eval1 = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_FINISHED_AT => now()->subDay(),
        ]);
        $metric1 = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $eval1->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_VALUE => 0.5,
            EvaluationMetric::FIELD_FINISHED_AT => now()->subDay(),
        ]);

        $eval2 = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_FINISHED_AT => now(),
        ]);
        $metric2 = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $eval2->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_VALUE => 0.8,
            EvaluationMetric::FIELD_FINISHED_AT => now(),
        ]);

        $previous = $this->service->getPrevious($metric2);

        $this->assertNotNull($previous);
        $this->assertEquals($metric1->id, $previous->id);
    }

    public function test_returns_null_when_no_previous(): void
    {
        [$model, $user] = $this->createModelAndUser();

        $eval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);
        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $eval->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_FINISHED_AT => now(),
        ]);

        $previous = $this->service->getPrevious($metric);

        $this->assertNull($previous);
    }

    public function test_excludes_archived_evaluations(): void
    {
        [$model, $user] = $this->createModelAndUser();

        $archivedEval = SearchEvaluation::factory()->finished()->archived()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_FINISHED_AT => now()->subDay(),
        ]);
        EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $archivedEval->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_VALUE => 0.5,
            EvaluationMetric::FIELD_FINISHED_AT => now()->subDay(),
        ]);

        $eval2 = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);
        $metric2 = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $eval2->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_FINISHED_AT => now(),
        ]);

        $previous = $this->service->getPrevious($metric2);

        $this->assertNull($previous);
    }

    public function test_excludes_same_evaluation(): void
    {
        [$model, $user] = $this->createModelAndUser();

        $eval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);
        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $eval->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_FINISHED_AT => now(),
        ]);

        $previous = $this->service->getPrevious($metric);

        $this->assertNull($previous);
    }

    public function test_matches_scorer_type_and_num_results(): void
    {
        [$model, $user] = $this->createModelAndUser();

        $eval1 = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_FINISHED_AT => now()->subDay(),
        ]);
        // Different scorer type
        EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $eval1->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'ndcg',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_FINISHED_AT => now()->subDay(),
        ]);

        $eval2 = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);
        $metric2 = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $eval2->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_FINISHED_AT => now(),
        ]);

        $previous = $this->service->getPrevious($metric2);

        // Should not match because scorer types differ
        $this->assertNull($previous);
    }
}
