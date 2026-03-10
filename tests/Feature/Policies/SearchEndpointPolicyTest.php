<?php

namespace Tests\Feature\Policies;

use App\Models\SearchEndpoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchEndpointPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private SearchEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $this->endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $this->user->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
        ]);
    }

    public function test_owner_can_manage_endpoint(): void
    {
        $this->assertTrue($this->user->can('create-endpoint', $this->user->currentTeam));
        $this->assertTrue($this->user->can('update', $this->endpoint));
        $this->assertTrue($this->user->can('delete', $this->endpoint));
        $this->assertTrue($this->user->can('toggle', $this->endpoint));
    }

    public function test_admin_can_manage_endpoint(): void
    {
        $admin = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($admin, ['role' => 'admin']);
        $admin->switchTeam($team);

        $this->assertTrue($admin->can('update', $this->endpoint));
        $this->assertTrue($admin->can('delete', $this->endpoint));
        $this->assertTrue($admin->can('toggle', $this->endpoint));
    }

    public function test_evaluator_cannot_manage_endpoint(): void
    {
        $evaluator = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($evaluator, ['role' => 'evaluator']);
        $evaluator->switchTeam($team);

        $this->assertFalse($evaluator->can('update', $this->endpoint));
        $this->assertFalse($evaluator->can('delete', $this->endpoint));
        $this->assertFalse($evaluator->can('toggle', $this->endpoint));
    }

    public function test_cross_team_user_cannot_manage_endpoint(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($otherUser, ['role' => 'admin']);

        // current_team_id is still the other user's personal team
        $this->assertNotEquals($otherUser->current_team_id, $this->endpoint->team_id);

        $this->assertFalse($otherUser->can('update', $this->endpoint));
        $this->assertFalse($otherUser->can('delete', $this->endpoint));
        $this->assertFalse($otherUser->can('toggle', $this->endpoint));
    }

    public function test_cross_team_user_cannot_create_endpoint(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($otherUser, ['role' => 'admin']);

        $this->assertFalse($otherUser->can('create-endpoint', $team));
    }

    public function test_cross_team_user_can_manage_after_switching(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($otherUser, ['role' => 'admin']);
        $otherUser->switchTeam($team);

        $this->assertTrue($otherUser->can('update', $this->endpoint));
        $this->assertTrue($otherUser->can('delete', $this->endpoint));
        $this->assertTrue($otherUser->can('toggle', $this->endpoint));
        $this->assertTrue($otherUser->can('create-endpoint', $team));
    }
}
