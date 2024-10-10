<?php

namespace App\Livewire;

use App\Models\Team;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Policies\Roles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Jetstream\Actions\UpdateTeamMemberRole;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Role;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * @property User $user
 * @property array<Role> $roles
 */
class TeamMemberManager extends Component
{
    public Team $team;

    public string $teamName = '';

    /**
     * Indicates if a user's role is currently being managed.
     *
     * @var bool
     */
    public bool $currentlyManagingRole = false;

    /**
     * The user that is having their role managed.
     *
     * @var User
     */
    public $managingRoleFor;

    /**
     * The current role for the user that is having their role managed.
     *
     * @var string
     */
    public $currentRole;

    /**
     * Indicates if the application is confirming if a user wishes to leave the current team.
     *
     * @var bool
     */
    public bool $confirmingLeavingTeam = false;

    /**
     * Indicates if the application is confirming if a team member should be removed.
     *
     * @var bool
     */
    public bool $confirmingTeamMemberRemoval = false;

    /**
     * The ID of the team member being removed.
     *
     * @var int|null
     */
    public ?int $teamMemberIdBeingRemoved = null;

    /**
     * The "add team member" form state.
     *
     * @var array
     */
    public $addTeamMemberForm = [
        'email' => '',
        'role' => null,
    ];

    public function mount($team = null): void
    {
        $this->team = $team ?? Auth::user()->currentTeam;
        $this->teamName = $this->team->name;
    }

    /**
     * Add a new team member to a team.
     *
     * @return void
     */
    public function addTeamMember(): void
    {
        $this->resetErrorBag();

        try {
            if (Features::sendsTeamInvitations()) {
                app(InvitesTeamMembers::class)->invite(
                    $this->user,
                    $this->team,
                    $this->addTeamMemberForm['email'],
                    $this->addTeamMemberForm['role']
                );
            } else {
                app(AddsTeamMembers::class)->add(
                    $this->user,
                    $this->team,
                    $this->addTeamMemberForm['email'],
                    $this->addTeamMemberForm['role']
                );
            }
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());
            return;
        }

        $this->addTeamMemberForm = [
            'email' => '',
            'role' => null,
        ];

        $this->team = $this->team->fresh();

        $this->dispatch('saved');
    }

    /**
     * Cancel a pending team member invitation.
     *
     * @param  int  $invitationId
     * @return void
     */
    public function cancelTeamInvitation(int $invitationId): void
    {
        if (!empty($invitationId)) {
            $model = Jetstream::teamInvitationModel();

            $model::whereKey($invitationId)->delete();

            Toaster::success('Team invitation cancelled successfully.');
        }

        $this->team = $this->team->fresh();
    }

    /**
     * Allow the given user's role to be managed.
     *
     * @param  int  $userId
     * @return void
     */
    public function manageRole(int $userId): void
    {
        $this->currentlyManagingRole = true;
        $this->managingRoleFor = Jetstream::findUserByIdOrFail($userId);
        $this->currentRole = $this->managingRoleFor->teamRole($this->team)->key;
    }

    /**
     * Save the role for the user being managed.
     *
     * @param UpdateTeamMemberRole $updater
     * @return void
     */
    public function updateRole(UpdateTeamMemberRole $updater): void
    {
        $roleChanged = $this->currentRole !== $this->managingRoleFor->teamRole($this->team)->key;

        try {
            $updater->update(
                $this->user,
                $this->team,
                $this->managingRoleFor->id,
                $this->currentRole
            );
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());
            return;
        } finally {
            $this->stopManagingRole();
        }

        $this->team = $this->team->fresh();

        if ($roleChanged) {
            Toaster::success('Role updated successfully.');

            $this->managingRoleFor->notify(
                new SystemNotification(
                    sprintf(
                        'Your role on the team <b>%s</b> has been changed to <b>%s</b>.',
                        $this->team->name,
                        $this->managingRoleFor->teamRole($this->team)->name
                    )
                )
            );
        }
    }

    /**
     * Stop managing the role of a given user.
     *
     * @return void
     */
    public function stopManagingRole(): void
    {
        $this->currentlyManagingRole = false;
    }

    public function leaveTeam(RemovesTeamMembers $remover): mixed
    {
        try {
            $remover->remove(
                $this->user,
                $this->team,
                $this->user
            );
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
            return null;
        } finally {
            $this->confirmingLeavingTeam = false;
        }

        $this->team = $this->team->fresh();

        return redirect(config('fortify.home'));
    }

    /**
     * Confirm that the given team member should be removed.
     *
     * @param  int  $userId
     * @return void
     */
    public function confirmTeamMemberRemoval(int $userId): void
    {
        $this->confirmingTeamMemberRemoval = true;
        $this->teamMemberIdBeingRemoved = $userId;
    }

    /**
     * Remove a team member from the team.
     *
     * @param RemovesTeamMembers $remover
     * @return void
     */
    public function removeTeamMember(RemovesTeamMembers $remover): void
    {
        try {
            $remover->remove(
                $this->user,
                $this->team,
                Jetstream::findUserByIdOrFail($this->teamMemberIdBeingRemoved)
            );
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }

        $this->confirmingTeamMemberRemoval = false;
        $this->teamMemberIdBeingRemoved = null;

        $this->team = $this->team->fresh();
    }

    /**
     * Get the current user of the application.
     *
     * @return User
     */
    public function getUserProperty(): User
    {
        return Auth::user();
    }

    /**
     * Get the available team member roles.
     *
     * @return array<Role>
     */
    public function getRolesProperty(): array
    {
        return Roles::getRoles();
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render(): View
    {
        return view('teams.team-member-manager');
    }
}
