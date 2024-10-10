<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;
use Laravel\Jetstream\Events\TeamMemberRemoved;
use Masmerise\Toaster\Toaster;

class RemoveTeamMember implements RemovesTeamMembers
{
    /**
     * Remove the team member from the given team.
     */
    public function remove(User $user, Team $team, User $teamMember): void
    {
        $this->authorize($user, $team, $teamMember);

        $this->ensureUserDoesNotOwnTeam($teamMember, $team);

        $team->removeUser($teamMember);

        TeamMemberRemoved::dispatch($team, $teamMember);

        if ($user->id === $teamMember->id) {
            Toaster::success('You have left the team.');
        } else {
            Toaster::success('Team member removed successfully.');

            $teamMember->notify(
                new SystemNotification(sprintf('You have been removed from the team <b>%s</b>.', $team->name))
            );
        }
    }

    /**
     * Authorize that the user can remove the team member.
     */
    protected function authorize(User $user, Team $team, User $teamMember): void
    {
        if (! Gate::forUser($user)->check('removeTeamMember', $team) &&
            $user->id !== $teamMember->id) {
            throw new AuthorizationException;
        }
    }

    /**
     * Ensure that the currently authenticated user does not own the team.
     */
    protected function ensureUserDoesNotOwnTeam(User $teamMember, Team $team): void
    {
        if ($teamMember->id === $team->owner->id) {
            throw ValidationException::withMessages([
                'team' => [__('User cannot leave a team they own.')],
            ])->errorBag('removeTeamMember');
        }
    }
}
