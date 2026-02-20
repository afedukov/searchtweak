<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Models;
use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModelsTeamScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_model_loads_own_team_model(): void
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

        Livewire::actingAs($user)
            ->test(Models::class)
            ->call('editModel', $model)
            ->assertSet('editModelModal', true)
            ->assertSet('modelForm.name', $model->name);
    }

    public function test_edit_model_rejects_other_team_model(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherUser = User::factory()->withPersonalTeam()->create();

        $otherEndpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $otherUser->id,
            SearchEndpoint::FIELD_TEAM_ID => $otherUser->currentTeam->id,
        ]);
        $otherModel = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $otherUser->id,
            SearchModel::FIELD_TEAM_ID => $otherUser->currentTeam->id,
            SearchModel::FIELD_ENDPOINT_ID => $otherEndpoint->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(Models::class)
            ->call('editModel', $otherModel);
    }

    public function test_clone_model_loads_own_team_model(): void
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

        Livewire::actingAs($user)
            ->test(Models::class)
            ->call('cloneModel', $model)
            ->assertSet('editModelModal', true)
            ->assertSet('modelForm.name', $model->name . ' clone');
    }

    public function test_clone_model_rejects_other_team_model(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherUser = User::factory()->withPersonalTeam()->create();

        $otherEndpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $otherUser->id,
            SearchEndpoint::FIELD_TEAM_ID => $otherUser->currentTeam->id,
        ]);
        $otherModel = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $otherUser->id,
            SearchModel::FIELD_TEAM_ID => $otherUser->currentTeam->id,
            SearchModel::FIELD_ENDPOINT_ID => $otherEndpoint->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(Models::class)
            ->call('cloneModel', $otherModel);
    }
}
