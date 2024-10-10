<?php

namespace App\Livewire;

use App\Livewire\Traits\SwitchTeamTrait;
use App\Models\Team;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;
use Laravel\Jetstream\RedirectsActions;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class Teams extends Component
{
    use WithPagination;
    use RedirectsActions;
    use SwitchTeamTrait;

    public const int PER_PAGE = 10;

    public array $state = [];

    public ?int $teamId = null;

    public bool $confirmingLeavingTeam = false;

    public bool $createTeamModal = false;

    public function render(): View
    {
        return view('livewire.pages.teams', [
            'ownedTeams' => \Auth::user()
                ->ownedTeams()
                ->latest(Team::FIELD_ID)
                ->with('owner')
                ->withCount('users')
                ->paginate(self::PER_PAGE, pageName: 'o'),

            'teams' => \Auth::user()
                ->teams()
                ->latest(Team::FIELD_ID)
                ->with('owner', 'users')
                ->withCount('users')
                ->paginate(self::PER_PAGE, pageName: 'p'),
        ])->title('Teams');
    }

    public function leaveTeam(RemovesTeamMembers $remover): void
    {
        try {
            $remover->remove(
                Auth::user(),
                Team::findOrFail($this->teamId),
                Auth::user()
            );
        } catch (ModelNotFoundException) {
            Toaster::error('Team not found.');
        } catch (\Throwable $e) {
            Toaster::error($e->getMessage());
        }

        $this->confirmingLeavingTeam = false;
    }

    public function createTeam(CreatesTeams $creator): void
    {
        $this->resetErrorBag();

        try {
            $creator->create(Auth::user(), $this->state);

            Toaster::success('Team created successfully.');
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());
        }

        $this->state = [];
        $this->createTeamModal = false;
    }
}
