<?php

namespace App\Livewire;

use App\Services\Leaderboard\LeaderboardQueryGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
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
        $query = $queryGenerator->getUserQuery($teamId, $dates)
            ->when($this->filterTagId, fn (Builder $query) =>
                $query->whereHas('user.tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
            );

        $dataset = $queryGenerator->getUserDataset(clone $query);

        return view('livewire.pages.leaderboard', [
            'team' => Auth::user()->currentTeam,
            'items' => $query
                ->with('user.tags')
                ->paginate(self::PER_PAGE),
            'dataset' => $dataset,
            'showType' => 'users',
        ])->title('Leaderboard');
    }

    private function renderJudges(LeaderboardQueryGenerator $queryGenerator, int $teamId, array $dates): View
    {
        $query = $queryGenerator->getJudgeQuery($teamId, $dates)
            ->when($this->filterTagId, fn (Builder $query) =>
                $query->whereHas('judge.tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
            );

        $dataset = $queryGenerator->getJudgeDataset(clone $query);

        return view('livewire.pages.leaderboard', [
            'team' => Auth::user()->currentTeam,
            'items' => $query->paginate(self::PER_PAGE),
            'dataset' => $dataset,
            'showType' => 'judges',
        ])->title('Leaderboard');
    }

    private function renderAll(LeaderboardQueryGenerator $queryGenerator, int $teamId, array $dates): View
    {
        $userQuery = $queryGenerator->getUserQuery($teamId, $dates);
        $judgeQuery = $queryGenerator->getJudgeQuery($teamId, $dates);

        $users = $userQuery
            ->with('user.tags')
            ->when($this->filterTagId, fn (Builder $query) =>
                $query->whereHas('user.tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
            )
            ->get();

        $judges = $judgeQuery
            ->when($this->filterTagId, fn (Builder $query) =>
                $query->whereHas('judge.tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
            )
            ->get();

        $allItems = $this->buildAllItems($users, $judges);
        $dataset = $allItems
            ->take(10)
            ->map(fn (object $item) => [
                'label' => $item->entry_type === self::FILTER_TYPE_JUDGES
                    ? (($item->judge?->name ?? 'Removed Judge') . ' (AI)')
                    : ($item->user?->name ?? 'Removed User'),
                'value' => $item->feedback_count,
            ])
            ->values()
            ->all();

        return view('livewire.pages.leaderboard', [
            'team' => Auth::user()->currentTeam,
            'items' => $this->paginateCollection($allItems, self::PER_PAGE),
            'dataset' => $dataset,
            'showType' => 'all',
        ])->title('Leaderboard');
    }

    private function buildAllItems(Collection $users, Collection $judges): Collection
    {
        $userItems = collect($users->all())->map(fn (object $item) => (object) [
            'entry_type' => self::FILTER_TYPE_USERS,
            'user' => $item->user,
            'judge' => null,
            'feedback_count' => (int) $item->feedback_count,
        ]);

        $judgeItems = collect($judges->all())->map(fn (object $item) => (object) [
            'entry_type' => self::FILTER_TYPE_JUDGES,
            'user' => null,
            'judge' => $item->judge,
            'feedback_count' => (int) $item->feedback_count,
        ]);

        return $userItems
            ->concat($judgeItems)
            ->sortByDesc('feedback_count')
            ->values()
            ->map(function (object $item, int $index) {
                $item->position = $index + 1;

                return $item;
            });
    }

    private function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage() ?: 1;
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
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
