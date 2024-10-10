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
     * Determine whether the user can create models.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, Permissions::PERMISSION_MANAGE_SEARCH_ENDPOINTS);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SearchEndpoint $searchEndpoint): bool
    {
        return $user->hasTeamPermission($searchEndpoint->team, Permissions::PERMISSION_MANAGE_SEARCH_ENDPOINTS);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SearchEndpoint $searchEndpoint): bool
    {
        return $user->hasTeamPermission($searchEndpoint->team, Permissions::PERMISSION_MANAGE_SEARCH_ENDPOINTS);
    }

    public function toggle(User $user, SearchEndpoint $searchEndpoint): bool
    {
        return $user->hasTeamPermission($searchEndpoint->team, Permissions::PERMISSION_MANAGE_SEARCH_ENDPOINTS);
    }
}
