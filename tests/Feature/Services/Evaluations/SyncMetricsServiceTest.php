<?php

namespace Tests\Feature\Services\Evaluations;

use App\Models\EvaluationMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\User;
use App\Services\Evaluations\SyncMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SyncMetricsService $service;
    private SearchEvaluation $evaluation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SyncMetricsService();

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
        $this->evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);
    }

    public function test_create_new_metrics(): void
    {
        $this->service->sync($this->evaluation, [
            [
                EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
                EvaluationMetric::FIELD_NUM_RESULTS => 10,
            ],
            [
                EvaluationMetric::FIELD_SCORER_TYPE => 'ndcg',
                EvaluationMetric::FIELD_NUM_RESULTS => 5,
            ],
        ]);

        $this->assertEquals(2, $this->evaluation->metrics()->count());

        $types = $this->evaluation->metrics()->pluck(EvaluationMetric::FIELD_SCORER_TYPE)->all();
        $this->assertContains('precision', $types);
        $this->assertContains('ndcg', $types);
    }

    public function test_delete_removed_metrics(): void
    {
        $this->service->sync($this->evaluation, [
            [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 10],
            [EvaluationMetric::FIELD_SCORER_TYPE => 'ndcg', EvaluationMetric::FIELD_NUM_RESULTS => 10],
        ]);

        $this->assertEquals(2, $this->evaluation->metrics()->count());

        $keepId = $this->evaluation->metrics()->where(EvaluationMetric::FIELD_SCORER_TYPE, 'precision')->first()->id;

        $this->service->sync($this->evaluation, [
            [
                EvaluationMetric::FIELD_ID => $keepId,
                EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
                EvaluationMetric::FIELD_NUM_RESULTS => 10,
            ],
        ]);

        $this->assertEquals(1, $this->evaluation->metrics()->count());
        $this->assertEquals('precision', $this->evaluation->metrics()->first()->scorer_type);
    }

    public function test_update_existing_metric(): void
    {
        $this->service->sync($this->evaluation, [
            [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 10],
        ]);

        $metric = $this->evaluation->metrics()->first();

        $this->service->sync($this->evaluation, [
            [
                EvaluationMetric::FIELD_ID => $metric->id,
                EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
                EvaluationMetric::FIELD_NUM_RESULTS => 5,
            ],
        ]);

        $this->assertEquals(1, $this->evaluation->metrics()->count());
        $this->assertEquals(5, $this->evaluation->metrics()->first()->num_results);
    }
}
