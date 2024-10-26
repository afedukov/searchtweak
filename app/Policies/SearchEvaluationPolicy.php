<?php

namespace App\Policies;

use App\Models\SearchEvaluation;
use App\Models\Team;
use App\Models\User;

class SearchEvaluationPolicy
{
    /**
     * Determine whether the user can view evaluations.
     */
    public function viewAny(User $user): bool
    {
        return $user->canCurrentTeam(Permissions::PERMISSION_MANAGE_SEARCH_EVALUATIONS);
    }

    /**
     * Determine whether the user can view leaderboard.
     */
    public function viewLeaderboard(User $user): bool
    {
        return $user->canCurrentTeam(Permissions::PERMISSION_VIEW_LEADERBOARD);
    }

    /**
     * Determine whether the user can view search evaluation.
     */
    public function view(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $this->canManageEvaluation($user, $searchEvaluation);
    }

    /**
     * Determine whether the user can view user feedbacks for search evaluation.
     */
    public function viewFeedback(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $user->current_team_id === $searchEvaluation->model->team_id &&
            $user->hasTeamPermission($searchEvaluation->model->team, Permissions::PERMISSION_MANAGE_USER_FEEDBACK);
    }

    /**
     * Determine whether the user can give feedback for given search evaluation.
     */
    public function giveFeedback(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $user->current_team_id === $searchEvaluation->model->team_id &&
            $user->hasTeamPermission($searchEvaluation->model->team, Permissions::PERMISSION_GIVE_USER_FEEDBACK);
    }

    /**
     * Determine whether the user can grade snapshot.
     */
    public function gradeSnapshot(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $user->current_team_id === $searchEvaluation->model->team_id &&
            $user->hasTeamPermission($searchEvaluation->model->team, Permissions::PERMISSION_MANAGE_USER_FEEDBACK);
    }

    /**
     * Determine whether the user can reset snapshot.
     */
    public function resetSnapshot(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $user->current_team_id === $searchEvaluation->model->team_id &&
            $user->hasTeamPermission($searchEvaluation->model->team, Permissions::PERMISSION_MANAGE_USER_FEEDBACK);
    }

    /**
     * Determine whether the user can give feedback from evaluation pool.
     */
    public function giveFeedbackEvaluationPool(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $user->current_team_id === $searchEvaluation->model->team_id &&
            $user->hasTeamPermission($searchEvaluation->model->team, Permissions::PERMISSION_MANAGE_USER_FEEDBACK);
    }

    /**
     * Determine whether the user can give feedback from global pool.
     */
    public function giveFeedbackGlobalPool(User $user): bool
    {
        return $user->canCurrentTeam(Permissions::PERMISSION_GIVE_USER_FEEDBACK);
    }

    /**
     * Determine whether the user can reset user feedback for search evaluation.
     */
    public function resetFeedback(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $user->current_team_id === $searchEvaluation->model->team_id &&
            $user->hasTeamPermission($searchEvaluation->model->team, Permissions::PERMISSION_MANAGE_USER_FEEDBACK);
    }

    /**
     * Determine whether the user can create search evaluation.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->current_team_id === $team->id &&
            $user->hasTeamPermission($team, Permissions::PERMISSION_MANAGE_SEARCH_EVALUATIONS);
    }

    /**
     * Determine whether the user can update the search evaluation.
     */
    public function update(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $this->canManageEvaluation($user, $searchEvaluation);
    }

    /**
     * Determine whether the user can delete the search evaluation.
     */
    public function delete(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $this->canManageEvaluation($user, $searchEvaluation);
    }

    /**
     * Determine whether the user can start the search evaluation.
     */
    public function start(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $this->canManageEvaluation($user, $searchEvaluation);
    }

    /**
     * Determine whether the user can pause the search evaluation.
     */
    public function pause(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $this->canManageEvaluation($user, $searchEvaluation);
    }

    /**
     * Determine whether the user can finish the search evaluation.
     */
    public function finish(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $this->canManageEvaluation($user, $searchEvaluation);
    }

    /**
     * Determine whether the user can export the search evaluation.
     */
    public function export(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $this->canManageEvaluation($user, $searchEvaluation);
    }

    private function canManageEvaluation(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $user->current_team_id === $searchEvaluation->model->team_id &&
            $user->hasTeamPermission($searchEvaluation->model->team, Permissions::PERMISSION_MANAGE_SEARCH_EVALUATIONS);
    }

    /**
     * Determine whether the user can archive/un-archive the search evaluation.
     */
    public function archive(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $this->canManageEvaluation($user, $searchEvaluation);
    }

    /**
     * Determine whether the user can pin/un-pin the search evaluation.
     */
    public function pin(User $user, SearchEvaluation $searchEvaluation): bool
    {
        return $this->canManageEvaluation($user, $searchEvaluation);
    }
}
