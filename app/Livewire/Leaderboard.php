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

    public const string FILTER_TYPE_ALL = 'all';
    public const string FILTER_TYPE_USERS = 'users';
    public const string FILTER_TYPE_JUDGES = 'judges';

    public string $date;

    public int $filterTagId = 0;

    public string $filterType = self::FILTER_TYPE_ALL;

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

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function render(LeaderboardQueryGenerator $queryGenerator): View
    {
        $teamId = Auth::user()->current_team_id;
        $dates = $this->getDates();

        if ($this->filterType === self::FILTER_TYPE_JUDGES) {
            return $this->renderJudges($queryGenerator, $teamId, $dates);
        }

        if ($this->filterType === self::FILTER_TYPE_USERS) {
            return $this->renderUsers($queryGenerator, $teamId, $dates);
        }

        // All: show users and judges combined
        return $this->renderAll($queryGenerator, $teamId, $dates);
    }

    private function renderUsers(LeaderboardQueryGenerator $queryGenerator, int $teamId, array $dates): View
    {
        $query = $queryGenerator->getUserQuery($teamId, $dates);
        $dataset = $queryGenerator->getUserDataset($query);

        return view('livewire.pages.leaderboard', [
            'team' => Auth::user()->currentTeam,
            'items' => $query
                ->with('user.tags')
                ->when($this->filterTagId, fn (Builder $query) =>
                    $query->whereHas('user.tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
                )
                ->paginate(self::PER_PAGE),
            'dataset' => $dataset,
            'showType' => 'users',
        ])->title('Leaderboard');
    }

    private function renderJudges(LeaderboardQueryGenerator $queryGenerator, int $teamId, array $dates): View
    {
        $query = $queryGenerator->getJudgeQuery($teamId, $dates);
        $dataset = $queryGenerator->getJudgeDataset($query);

        return view('livewire.pages.leaderboard', [
            'team' => Auth::user()->currentTeam,
            'items' => $query
                ->when($this->filterTagId, fn (Builder $query) =>
                    $query->whereHas('judge.tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
                )
                ->paginate(self::PER_PAGE),
            'dataset' => $dataset,
            'showType' => 'judges',
        ])->title('Leaderboard');
    }

    private function renderAll(LeaderboardQueryGenerator $queryGenerator, int $teamId, array $dates): View
    {
        // For "All" view, show users first then judges as separate sections
        $userQuery = $queryGenerator->getUserQuery($teamId, $dates);
        $judgeQuery = $queryGenerator->getJudgeQuery($teamId, $dates);
        $dataset = $queryGenerator->getUserDataset($userQuery);

        return view('livewire.pages.leaderboard', [
            'team' => Auth::user()->currentTeam,
            'items' => $userQuery
                ->with('user.tags')
                ->when($this->filterTagId, fn (Builder $query) =>
                    $query->whereHas('user.tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
                )
                ->paginate(self::PER_PAGE),
            'judgeItems' => $judgeQuery
                ->when($this->filterTagId, fn (Builder $query) =>
                    $query->whereHas('judge.tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
                )
                ->get(),
            'dataset' => $dataset,
            'showType' => 'all',
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
