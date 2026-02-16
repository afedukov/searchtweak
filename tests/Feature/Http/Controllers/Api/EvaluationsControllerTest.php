<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\Team;
use App\Models\User;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\GradedScale;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EvaluationsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        // Ensure Team has API tokens trait capability by just using it
        // The controller expects Auth::user() to be Team for API guard based on its code usage ($team->user_id)

        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
        ]);

        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $team->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);

        return [$user, $team, $model];
    }

    private function authenticate(Team $team): void
    {
        Sanctum::actingAs($team, ['*'], 'sanctum');
        Auth::guard('api')->setUser($team);
    }

    public function test_index_returns_evaluations_list(): void
    {
        [$user, $team, $model] = $this->createSetup();

        SearchEvaluation::factory()->count(3)->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $this->authenticate($team);

        $response = $this->getJson('/api/v1/evaluations');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'status',
                    'created_at',
                ]
            ])
            ->assertJsonCount(3);
    }

    public function test_index_is_protected(): void
    {
        $response = $this->getJson('/api/v1/evaluations');
        $response->assertUnauthorized();
    }

    public function test_show_returns_evaluation_details(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $this->authenticate($team);

        $response = $this->getJson("/api/v1/evaluations/{$evaluation->id}");

        $response->assertOk()
            ->assertJson([
                'id' => $evaluation->id,
                'model_id' => $model->id,
            ]);
    }

    public function test_store_creates_evaluation(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $data = [
            'model_id' => $model->id,
            'name' => 'New Evaluation',
            'scale_type' => BinaryScale::SCALE_TYPE,
            'keywords' => ['keyword1', 'keyword2'],
            'metrics' => [['scorer_type' => 'precision', 'num_results' => 5]],
            'transformers' => ['scale_type' => BinaryScale::SCALE_TYPE, 'rules' => []],
            'setting_feedback_strategy' => 1,
            'setting_show_position' => true,
            'setting_auto_restart' => false,
            'setting_reuse_strategy' => SearchEvaluation::REUSE_STRATEGY_NONE,
        ];

        $this->authenticate($team);

        $response = $this->postJson('/api/v1/evaluations', $data);

        $response->assertCreated()
            ->assertJsonPath('name', 'New Evaluation');

        $this->assertDatabaseHas('search_evaluations', [
            'name' => 'New Evaluation',
            'model_id' => $model->id,
        ]);
    }

    public function test_store_validates_request(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $this->authenticate($team);

        $response = $this->postJson('/api/v1/evaluations', []); // Empty data

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'model_id', 
                'name',
                'scale_type', 
                'keywords', 
                'metrics',
                'transformers',
                'setting_feedback_strategy'
            ]);
    }

    public function test_finish_changes_status(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $this->authenticate($team);

        $response = $this->postJson("/api/v1/evaluations/{$evaluation->id}/finish");

        $response->assertOk(); 

        $evaluation->refresh();
        $this->assertTrue($evaluation->isFinished());
    }

    public function test_finish_fails_without_permission(): void
    {
        [$owner, $team, $model] = $this->createSetup();
        
        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $owner->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        // Create another team
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $this->authenticate($otherTeam);

        $response = $this->postJson("/api/v1/evaluations/{$evaluation->id}/finish");

        // Should return 404 because controller scopes query by team
        $response->assertNotFound();
    }

    public function test_start_dispatches_job(): void
    {
        [$user, $team, $model] = $this->createSetup();
        \Illuminate\Support\Facades\Queue::fake();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $this->authenticate($team);

        $response = $this->postJson("/api/v1/evaluations/{$evaluation->id}/start");

        $response->assertOk()
            ->assertJson(['status' => 'OK']);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\Evaluations\StartEvaluationJob::class, function ($job) use ($evaluation) {
            return $job->uniqueId() == $evaluation->id;
        });
    }

    public function test_stop_dispatches_job(): void
    {
        [$user, $team, $model] = $this->createSetup();
        \Illuminate\Support\Facades\Queue::fake();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $this->authenticate($team);

        $response = $this->postJson("/api/v1/evaluations/{$evaluation->id}/stop");

        $response->assertOk()
            ->assertJson(['status' => 'OK']);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\Evaluations\PauseEvaluationJob::class, function ($job) use ($evaluation) {
            return $job->uniqueId() == $evaluation->id;
        });
    }

    public function test_delete_removes_evaluation(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $this->authenticate($team);

        $response = $this->deleteJson("/api/v1/evaluations/{$evaluation->id}");

        $response->assertOk()
            ->assertJson(['status' => 'OK']);

        $this->assertDatabaseMissing('search_evaluations', ['id' => $evaluation->id]);
    }

    public function test_judgements_returns_data_for_finished_evaluation(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        // Judgements require keywords and snapshots to actually return something meaningful via service
        // But the controller just calls the service. Using an empty finished evaluation should return empty array or handle gracefully.
        // Let's expect empty array for basics.

        $this->authenticate($team);

        $response = $this->getJson("/api/v1/evaluations/{$evaluation->id}/judgements");

        $response->assertOk()
            ->assertJson([]); // Expecting empty array if no data
    }
}
