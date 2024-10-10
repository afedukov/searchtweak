<?php

namespace App\Livewire;

use App\Services\Leaderboard\LeaderboardQueryGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Jetstream\RedirectsActions;
use Livewire\Component;
use Livewire\WithPagination;

class Leaderboard extends Component
{
    use WithPagination;
    use RedirectsActions;

    public const int PER_PAGE = 10;

    public string $date;

    public int $filterTagId = 0;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:team.%d,.evaluation.feedback.changed', Auth::user()->current_team_id) => '$refresh',
        ];
    }

    public function mount(): void
    {
        $this->date = sprintf('%s - %s',
            Carbon::now()->subDays(6)->format('M j, Y'),
            Carbon::now()->format('M j, Y')
        );
    }

    public function render(LeaderboardQueryGenerator $queryGenerator): View
    {
        $query = $queryGenerator->getQuery(Auth::user()->current_team_id, $this->getDates());
        $dataset = $queryGenerator->getDataset($query);

        return view('livewire.pages.leaderboard', [
            'team' => Auth::user()->currentTeam,
            'items' => $query
                ->with('user.tags')
                ->when($this->filterTagId, fn (Builder $query) =>
                    $query->whereHas('user.tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
                )
                ->paginate(self::PER_PAGE),
            'dataset' => $dataset,
        ])->title('Leaderboard');
    }

    /**
     * @return array<Carbon>
     */
    private function getDates(): array
    {
        $dates = explode('-', $this->date);

        if (count($dates) === 1) {
            return [
                Carbon::parse($dates[0])->startOfDay(),
                Carbon::parse($dates[0])->endOfDay(),
            ];
        }

        return [
            Carbon::parse($dates[0])->startOfDay(),
            Carbon::parse($dates[1])->endOfDay(),
        ];
    }
}
