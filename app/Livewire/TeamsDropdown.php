<?php

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class TeamsDropdown extends Component
{
    protected $listeners = ['team-switched' => '$refresh'];

    public function render(): View
    {
        return view('livewire.teams-dropdown');
    }
}
