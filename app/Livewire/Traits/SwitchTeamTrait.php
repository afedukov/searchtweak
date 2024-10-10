<?php

namespace App\Livewire\Traits;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Masmerise\Toaster\Toaster;

trait SwitchTeamTrait
{
    public function switchTeam(Team $team, string $route = 'dashboard'): mixed
    {
        if (Auth::user()->switchTeam($team)) {
            return redirect(route($route));
        }

        Toaster::error(__('Failed to switch team.'));
        return null;
    }

    public function switchTeamEvent(Team $team): void
    {
        if (Auth::user()->switchTeam($team)) {
            $this->dispatch('team-switched');
        } else {
            Toaster::error(__('Failed to switch team.'));
        }
    }
}
