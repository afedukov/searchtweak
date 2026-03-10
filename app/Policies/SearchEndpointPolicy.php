<?php

namespace App\Policies;

use App\Models\SearchEndpoint;
use App\Models\Team;
use App\Models\User;

class SearchEndpointPolicy
{
    /**
     * Determine whether the user can view endpoints.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasTeamPermission($user->currentTeam, Permissions::PERMISSION_MANAGE_SEARCH_ENDPOINTS);
    }

    /**
     * Determine whether the user can create endpoints.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->current_team_id === $team->id &&
            $user->hasTeamPermission($team, Permissions::PERMISSION_MANAGE_SEARCH_ENDPOINTS);
    }

    /**
     * Determine whether the user can update the endpoint.
     */
    public function update(User $user, SearchEndpoint $searchEndpoint): bool
    {
        return $this->canManageEndpoint($user, $searchEndpoint);
    }

    /**
     * Determine whether the user can delete the endpoint.
     */
    public function delete(User $user, SearchEndpoint $searchEndpoint): bool
    {
        return $this->canManageEndpoint($user, $searchEndpoint);
    }

    public function toggle(User $user, SearchEndpoint $searchEndpoint): bool
    {
        return $this->canManageEndpoint($user, $searchEndpoint);
    }

    private function canManageEndpoint(User $user, SearchEndpoint $searchEndpoint): bool
    {
        return $user->current_team_id === $searchEndpoint->team_id &&
            $user->hasTeamPermission($searchEndpoint->team, Permissions::PERMISSION_MANAGE_SEARCH_ENDPOINTS);
    }
}
