<?php

namespace App\Livewire\Widgets;

use App\Services\Leaderboard\LeaderboardQueryGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LeaderboardWidget extends BaseWidget
{
    public static function getWidgetName(array $data = null): string
    {
        return 'Leaderboard';
    }

    public static function isRemovable(): bool
    {
        return false;
    }

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:team.%d,.evaluation.feedback.changed', Auth::user()->current_team_id) => '$refresh',
        ];
    }

    public function render(): View
    {
        $query = app(LeaderboardQueryGenerator::class)
            ->getQuery(Auth::user()->current_team_id, [
                Carbon::now()->subDays(6)->startOfDay(),
                Carbon::now()->endOfDay(),
            ]);

        $dataset = app(LeaderboardQueryGenerator::class)
            ->getDataset($query, 5);

        return view('livewire.widgets.leaderboard-widget', [
            'dataset' => $dataset,
        ]);
    }
}
