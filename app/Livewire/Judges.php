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

    public const string FILTER_STATUS_ALL = 'all';
    public const string FILTER_STATUS_ACTIVE = 'active';
    public const string FILTER_STATUS_ARCHIVED = 'archived';

    public const string SESSION_FILTER_STATUS = 'judge-filter-status';

    public bool $confirmingJudgeRemoval = false;
    public ?int $judgeIdBeingRemoved = null;

    public string $filterStatusMode = self::FILTER_STATUS_ALL;

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
            $sessionFilterStatus = Session::get(self::SESSION_FILTER_STATUS);

            if (in_array($sessionFilterStatus, [
                self::FILTER_STATUS_ALL,
                self::FILTER_STATUS_ACTIVE,
                self::FILTER_STATUS_ARCHIVED,
            ], true)) {
                $this->filterStatusMode = $sessionFilterStatus;
            }
        }
    }

    public function render(): View
    {
        Session::put(self::SESSION_FILTER_STATUS, $this->filterStatusMode);

        $query = Auth::user()->currentTeam
            ->judges()
            ->with(['user', 'team', 'tags']);

        if ($this->filterStatusMode === self::FILTER_STATUS_ARCHIVED) {
            $query->whereNotNull(Judge::FIELD_ARCHIVED_AT);
        } elseif ($this->filterStatusMode === self::FILTER_STATUS_ACTIVE) {
            $query->whereNull(Judge::FIELD_ARCHIVED_AT);
        }

        $judges = $query->paginate(self::PER_PAGE);

        return view('livewire.pages.judges', [
            'judges' => $judges,
        ])->title('Judges');
    }

    public function updatedFilterStatusMode(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->filterStatusMode = self::FILTER_STATUS_ALL;
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
