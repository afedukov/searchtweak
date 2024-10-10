<?php

namespace App\Livewire\Widgets;

use App\Livewire\Traits\SwitchTeamTrait;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class TeamsWidget extends BaseWidget
{
    use SwitchTeamTrait;

    /** @var array<Team> */
    public array $teams = [];

    public static function getWidgetName(array $data = null): string
    {
        return __('Teams');
    }

    public static function isRemovable(): bool
    {
        return false;
    }

    public function mount(): void
    {
        /** @var Collection $teams */
        $teams = \Auth::user()
            ->allTeams()
            ->sortByDesc('id')
            ->take(3);

        $this->teams = $teams
            ->loadMissing(['owner', 'users'])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.widgets.teams-widget');
    }
}
