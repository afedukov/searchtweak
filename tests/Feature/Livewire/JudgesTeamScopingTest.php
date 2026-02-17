<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Judges;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JudgesTeamScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_judge_loads_own_team_judge(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('editJudge', $judge)
            ->assertSet('editJudgeModal', true)
            ->assertSet('judgeForm.name', $judge->name);
    }

    public function test_edit_judge_rejects_other_team_judge(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherUser = User::factory()->withPersonalTeam()->create();

        $otherJudge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $otherUser->id,
            Judge::FIELD_TEAM_ID => $otherUser->currentTeam->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('editJudge', $otherJudge);
    }

    public function test_clone_judge_loads_own_team_judge(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('cloneJudge', $judge)
            ->assertSet('editJudgeModal', true)
            ->assertSet('judgeForm.name', $judge->name . ' clone');
    }

    public function test_clone_judge_rejects_other_team_judge(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherUser = User::factory()->withPersonalTeam()->create();

        $otherJudge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $otherUser->id,
            Judge::FIELD_TEAM_ID => $otherUser->currentTeam->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('cloneJudge', $otherJudge);
    }

    public function test_edit_judge_clears_api_key(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_API_KEY => 'secret-key-123',
        ]);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('editJudge', $judge)
            ->assertSet('judgeForm.api_key', '');
    }
}
