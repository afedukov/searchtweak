<?php

namespace App\Livewire;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Actions\ValidateTeamDeletion;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Laravel\Jetstream\RedirectsActions;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class DeleteTeamForm extends Component
{
    use RedirectsActions;

    /**
     * The team instance.
     *
     * @var mixed
     */
    public $team;

    /**
     * Indicates if team deletion is being confirmed.
     *
     * @var bool
     */
    public $confirmingTeamDeletion = false;

    /**
     * Mount the component.
     *
     * @param  mixed  $team
     * @return void
     */
    public function mount($team)
    {
        $this->team = $team;
    }

    /**
     * Delete the team.
     *
     * @param  \Laravel\Jetstream\Actions\ValidateTeamDeletion  $validator
     * @param  \Laravel\Jetstream\Contracts\DeletesTeams  $deleter
     * @return mixed
     */
    public function deleteTeam(ValidateTeamDeletion $validator, DeletesTeams $deleter)
    {
        try {
            $validator->validate(Auth::user(), $this->team);
            $this->validateTeam($this->team);
        } catch (ValidationException $e) {
            Toaster::error($e->getMessage());

            return null;
        }

        $deleter->delete($this->team);

        $this->team = null;

        Toaster::success('Team deleted successfully.');

        return $this->redirectPath($deleter);
    }

    private function validateTeam(Team $team): void
    {
        if ($team->endpoints()->exists() || $team->models()->exists()) {
            throw ValidationException::withMessages(['team' => 'You may not delete a team with resources. Please delete all resources before deleting this team.'])
                ->errorBag('deleteTeam');
        }
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('teams.delete-team-form');
    }
}
