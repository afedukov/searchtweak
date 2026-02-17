<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Endpoints;
use App\Models\SearchEndpoint;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EndpointsTeamScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_endpoint_loads_own_team_endpoint(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        Livewire::actingAs($user)
            ->test(Endpoints::class)
            ->call('editEndpoint', $endpoint)
            ->assertSet('editEndpointModal', true)
            ->assertSet('endpointForm.name', $endpoint->name);
    }

    public function test_edit_endpoint_rejects_other_team_endpoint(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherUser = User::factory()->withPersonalTeam()->create();

        $otherEndpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $otherUser->id,
            SearchEndpoint::FIELD_TEAM_ID => $otherUser->currentTeam->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(Endpoints::class)
            ->call('editEndpoint', $otherEndpoint);
    }

    public function test_clone_endpoint_loads_own_team_endpoint(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        Livewire::actingAs($user)
            ->test(Endpoints::class)
            ->call('cloneEndpoint', $endpoint)
            ->assertSet('editEndpointModal', true)
            ->assertSet('endpointForm.name', $endpoint->name . ' clone');
    }

    public function test_clone_endpoint_rejects_other_team_endpoint(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherUser = User::factory()->withPersonalTeam()->create();

        $otherEndpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $otherUser->id,
            SearchEndpoint::FIELD_TEAM_ID => $otherUser->currentTeam->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(Endpoints::class)
            ->call('cloneEndpoint', $otherEndpoint);
    }
}
