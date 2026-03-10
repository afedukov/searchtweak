<?php

namespace Tests\Feature\Policies;

use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchModelPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private SearchModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $this->user->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
        ]);

        $this->model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $this->user->id,
            SearchModel::FIELD_TEAM_ID => $team->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);
    }

    public function test_owner_can_view_model(): void
    {
        $this->assertTrue($this->user->can('view', $this->model));
    }

    public function test_owner_can_create_model(): void
    {
        $this->assertTrue($this->user->can('create-model', $this->user->currentTeam));
    }

    public function test_owner_can_update_model(): void
    {
        $this->assertTrue($this->user->can('update', $this->model));
    }

    public function test_owner_can_delete_model(): void
    {
        $this->assertTrue($this->user->can('delete', $this->model));
    }

    public function test_owner_can_pin_model(): void
    {
        $this->assertTrue($this->user->can('pin', $this->model));
    }

    public function test_admin_can_manage_model(): void
    {
        $admin = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($admin, ['role' => 'admin']);
        $admin->switchTeam($team);

        $this->assertTrue($admin->can('view', $this->model));
        $this->assertTrue($admin->can('update', $this->model));
        $this->assertTrue($admin->can('delete', $this->model));
        $this->assertTrue($admin->can('pin', $this->model));
    }

    public function test_evaluator_cannot_manage_model(): void
    {
        $evaluator = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($evaluator, ['role' => 'evaluator']);
        $evaluator->switchTeam($team);

        $this->assertFalse($evaluator->can('view', $this->model));
        $this->assertFalse($evaluator->can('update', $this->model));
        $this->assertFalse($evaluator->can('delete', $this->model));
        $this->assertFalse($evaluator->can('pin', $this->model));
    }

    public function test_cross_team_user_cannot_manage_model(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        // Add user to the model's team as admin, but keep their current_team_id pointing to their own team
        $team->users()->attach($otherUser, ['role' => 'admin']);

        // current_team_id is still the other user's personal team — not the model's team
        $this->assertNotEquals($otherUser->current_team_id, $this->model->team_id);

        $this->assertFalse($otherUser->can('view', $this->model));
        $this->assertFalse($otherUser->can('update', $this->model));
        $this->assertFalse($otherUser->can('delete', $this->model));
        $this->assertFalse($otherUser->can('pin', $this->model));
    }

    public function test_cross_team_user_cannot_create_model(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($otherUser, ['role' => 'admin']);

        // current_team_id != target team
        $this->assertFalse($otherUser->can('create-model', $team));
    }

    public function test_cross_team_user_can_manage_after_switching(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($otherUser, ['role' => 'admin']);
        $otherUser->switchTeam($team);

        $this->assertTrue($otherUser->can('view', $this->model));
        $this->assertTrue($otherUser->can('update', $this->model));
        $this->assertTrue($otherUser->can('delete', $this->model));
        $this->assertTrue($otherUser->can('pin', $this->model));
        $this->assertTrue($otherUser->can('create-model', $team));
    }
}
