<?php

namespace Tests\Feature\Actions\Evaluations;

use App\Actions\Evaluations\DeleteSearchEvaluation;
use App\Models\EvaluationMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\User;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteSearchEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private DeleteSearchEvaluation $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new DeleteSearchEvaluation();
    }

    private function createSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();

        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $user->currentTeam->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);

        return [$user, $model];
    }

    public function test_delete_pending_evaluation(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING,
        ]);

        $this->action->delete($evaluation);

        $this->assertDatabaseMissing('search_evaluations', ['id' => $evaluation->id]);
    }

    public function test_delete_finished_evaluation(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $this->action->delete($evaluation);

        $this->assertDatabaseMissing('search_evaluations', ['id' => $evaluation->id]);
    }

    public function test_delete_active_evaluation_throws(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => 1, // Non-zero so isFailed() returns false
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Evaluation cannot be deleted.');

        $this->action->delete($evaluation);
    }

    public function test_delete_also_removes_metrics(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $this->action->delete($evaluation);

        $this->assertDatabaseMissing('search_evaluations', ['id' => $evaluation->id]);
        $this->assertDatabaseMissing('evaluation_metrics', ['id' => $metric->id]);
    }
}
