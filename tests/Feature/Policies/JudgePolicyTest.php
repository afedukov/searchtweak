<?php

namespace Tests\Feature\Policies;

use App\Models\Judge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JudgePolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Judge $judge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $this->judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $this->user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);
    }

    public function test_owner_can_manage_judge(): void
    {
        $this->assertTrue($this->user->can('create-judge', $this->user->currentTeam));
        $this->assertTrue($this->user->can('update', $this->judge));
        $this->assertTrue($this->user->can('delete', $this->judge));
        $this->assertTrue($this->user->can('toggle', $this->judge));
    }

    public function test_admin_can_manage_judge(): void
    {
        $admin = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($admin, ['role' => 'admin']);
        $admin->switchTeam($team);

        $this->assertTrue($admin->can('update', $this->judge));
        $this->assertTrue($admin->can('delete', $this->judge));
        $this->assertTrue($admin->can('toggle', $this->judge));
    }

    public function test_evaluator_cannot_manage_judge(): void
    {
        $evaluator = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($evaluator, ['role' => 'evaluator']);
        $evaluator->switchTeam($team);

        $this->assertFalse($evaluator->can('update', $this->judge));
        $this->assertFalse($evaluator->can('delete', $this->judge));
        $this->assertFalse($evaluator->can('toggle', $this->judge));
    }

    public function test_cross_team_user_cannot_manage_judge(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($otherUser, ['role' => 'admin']);

        // current_team_id is still the other user's personal team
        $this->assertNotEquals($otherUser->current_team_id, $this->judge->team_id);

        $this->assertFalse($otherUser->can('update', $this->judge));
        $this->assertFalse($otherUser->can('delete', $this->judge));
        $this->assertFalse($otherUser->can('toggle', $this->judge));
    }

    public function test_cross_team_user_cannot_create_judge(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($otherUser, ['role' => 'admin']);

        $this->assertFalse($otherUser->can('create-judge', $team));
    }

    public function test_cross_team_user_can_manage_after_switching(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $team = $this->user->currentTeam;

        $team->users()->attach($otherUser, ['role' => 'admin']);
        $otherUser->switchTeam($team);

        $this->assertTrue($otherUser->can('update', $this->judge));
        $this->assertTrue($otherUser->can('delete', $this->judge));
        $this->assertTrue($otherUser->can('toggle', $this->judge));
        $this->assertTrue($otherUser->can('create-judge', $team));
    }
}
