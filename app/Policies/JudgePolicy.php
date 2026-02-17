<?php

namespace App\Policies;

use App\Models\Judge;
use App\Models\Team;
use App\Models\User;

class JudgePolicy
{
    /**
     * Determine whether the user can view judges.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasTeamPermission($user->currentTeam, Permissions::PERMISSION_MANAGE_JUDGES);
    }

    /**
     * Determine whether the user can create judges.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, Permissions::PERMISSION_MANAGE_JUDGES);
    }

    /**
     * Determine whether the user can update the judge.
     */
    public function update(User $user, Judge $judge): bool
    {
        return $user->hasTeamPermission($judge->team, Permissions::PERMISSION_MANAGE_JUDGES);
    }

    /**
     * Determine whether the user can delete the judge.
     */
    public function delete(User $user, Judge $judge): bool
    {
        return $user->hasTeamPermission($judge->team, Permissions::PERMISSION_MANAGE_JUDGES);
    }

    public function toggle(User $user, Judge $judge): bool
    {
        return $user->hasTeamPermission($judge->team, Permissions::PERMISSION_MANAGE_JUDGES);
    }
}
