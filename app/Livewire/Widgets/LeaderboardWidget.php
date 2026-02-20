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
        $queryGenerator = app(LeaderboardQueryGenerator::class);
        $dates = [
            Carbon::now()->subDays(6)->startOfDay(),
            Carbon::now()->endOfDay(),
        ];
        $teamId = Auth::user()->current_team_id;

        $users = $queryGenerator
            ->getUserQuery($teamId, $dates)
            ->get()
            ->map(fn (object $item) => [
                'label' => $item->user->name,
                'value' => (int) $item->feedback_count,
            ]);

        $judges = $queryGenerator
            ->getJudgeQuery($teamId, $dates)
            ->get()
            ->map(fn (object $item) => [
                'label' => ($item->judge?->name ?? 'Removed Judge') . ' (AI)',
                'value' => (int) $item->feedback_count,
            ]);

        $dataset = $users
            ->concat($judges)
            ->sortByDesc('value')
            ->take(5)
            ->values()
            ->all();

        return view('livewire.widgets.leaderboard-widget', [
            'dataset' => $dataset,
        ]);
    }
}
