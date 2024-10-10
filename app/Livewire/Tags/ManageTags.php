<?php

namespace App\Livewire\Tags;

use App\Livewire\Traits\Users\ManageTagsTrait;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class ManageTags extends Component
{
    use ManageTagsTrait;

    public string $id;
    public Team $team;

    public string $tooltip = '';

    public function mount(): void
    {
        $this->team = Auth::user()->currentTeam;

        $this->initializeManageTags();
    }

    public function render(): View
    {
        return view('livewire.tags.manage-tags');
    }
}
