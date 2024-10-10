<?php

namespace App\Policies;

use App\Models\SearchModel;
use App\Models\Team;
use App\Models\User;

class SearchModelPolicy
{
    /**
     * Determine whether the user can view search model.
     */
    public function view(User $user, SearchModel $searchModel): bool
    {
        return $user->current_team_id === $searchModel->team_id &&
            $user->hasTeamPermission($searchModel->team, Permissions::PERMISSION_MANAGE_SEARCH_MODELS);
    }

    /**
     * Determine whether the user can view models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasTeamPermission($user->currentTeam, Permissions::PERMISSION_MANAGE_SEARCH_MODELS);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, Permissions::PERMISSION_MANAGE_SEARCH_MODELS);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SearchModel $searchModel): bool
    {
        return $user->hasTeamPermission($searchModel->team, Permissions::PERMISSION_MANAGE_SEARCH_MODELS);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SearchModel $searchModel): bool
    {
        return $user->hasTeamPermission($searchModel->team, Permissions::PERMISSION_MANAGE_SEARCH_MODELS);
    }
}
