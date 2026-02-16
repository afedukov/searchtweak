<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use App\Models\Team;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ModelsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSetup(): array
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

        return [$user, $team, $model];
    }

    private function authenticate(Team $team): void
    {
        Sanctum::actingAs($team, ['*'], 'sanctum');
        Auth::guard('api')->setUser($team);
    }

    public function test_index_returns_models_list(): void
    {
        [$user, $team, $model] = $this->createSetup();

        // Create another model to ensure list works
        SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $team->id,
            SearchModel::FIELD_ENDPOINT_ID => $model->endpoint_id,
        ]);

        $this->authenticate($team);

        $response = $this->getJson('/api/v1/models');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'description',
                    'created_at',
                ]
            ])
            ->assertJsonCount(2);
    }

    public function test_index_is_protected(): void
    {
        $response = $this->getJson('/api/v1/models');
        $response->assertUnauthorized();
    }

    public function test_show_returns_model_details(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $this->authenticate($team);

        $response = $this->getJson("/api/v1/models/{$model->id}");

        $response->assertOk()
            ->assertJson([
                'id' => $model->id,
                'name' => $model->name,
            ]);
    }

    public function test_show_fails_without_permission(): void
    {
        [$owner, $team, $model] = $this->createSetup();

        // Create another team
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $this->authenticate($otherTeam);

        $response = $this->getJson("/api/v1/models/{$model->id}");

        // Should return 404 because controller scopes query by team
        $response->assertNotFound();
    }
}
