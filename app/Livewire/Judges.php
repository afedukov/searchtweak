<?php

namespace App\Livewire;

use App\Actions\Judges\DeleteJudge;
use App\Actions\Judges\ToggleJudgeActive;
use App\Livewire\Traits\Judges\EditJudgeModalTrait;
use App\Models\Judge;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Laravel\Jetstream\RedirectsActions;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class Judges extends Component
{
    use WithPagination;
    use RedirectsActions;
    use EditJudgeModalTrait;

    public const int PER_PAGE = 10;

    public const array DEFAULT_FILTER_STATUS = ['active', 'archived'];

    public const string SESSION_FILTER_STATUS = 'judge-filter-status';

    public bool $confirmingJudgeRemoval = false;
    public ?int $judgeIdBeingRemoved = null;

    public array $filterStatus = self::DEFAULT_FILTER_STATUS;

    protected function getListeners(): array
    {
        $teamId = Auth::user()->current_team_id;

        return [
            sprintf('echo-private:team.%d,.JudgeCreated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.JudgeUpdated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.JudgeDeleted', $teamId) => '$refresh',
        ];
    }

    public function mount(): void
    {
        if (Session::has(self::SESSION_FILTER_STATUS)) {
            $this->filterStatus = Session::get(self::SESSION_FILTER_STATUS);
        }
    }

    public function render(): View
    {
        Session::put(self::SESSION_FILTER_STATUS, $this->filterStatus);

        $query = Auth::user()->currentTeam
            ->judges()
            ->with(['user', 'team', 'tags']);

        if (in_array('archived', $this->filterStatus) && in_array('active', $this->filterStatus)) {
            // Do nothing
        } elseif (in_array('archived', $this->filterStatus)) {
            $query->whereNotNull(Judge::FIELD_ARCHIVED_AT);
        } elseif (in_array('active', $this->filterStatus)) {
            $query->whereNull(Judge::FIELD_ARCHIVED_AT);
        } else {
            $query->whereRaw('1 = 0');
        }

        $judges = $query->paginate(self::PER_PAGE);

        return view('livewire.pages.judges', [
            'filterStatuses' => array_map(
                fn (string $status) => [
                    'key' => $status,
                    'name' => ucfirst($status),
                ],
                self::DEFAULT_FILTER_STATUS
            ),
            'judges' => $judges,
        ])->title('Judges');
    }

    public function resetFilter(): void
    {
        $this->filterStatus = self::DEFAULT_FILTER_STATUS;

        Session::forget(self::SESSION_FILTER_STATUS);
    }

    public function deleteJudge(DeleteJudge $action): void
    {
        $judge = Auth::user()
            ->currentTeam
            ->judges()
            ->findOrFail($this->judgeIdBeingRemoved);

        try {
            $action->delete($judge);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());

            return;
        } finally {
            $this->confirmingJudgeRemoval = false;
            $this->judgeIdBeingRemoved = null;
        }
    }

    public function toggleJudgeActive(ToggleJudgeActive $action, int $judgeId): void
    {
        $judge = Auth::user()
            ->currentTeam
            ->judges()
            ->findOrFail($judgeId);

        try {
            $action->toggle($judge);
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());

            return;
        }
    }
}
