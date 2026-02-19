<?php

namespace App\Livewire;

use App\Models\Judge;
use App\Models\JudgeLog;
use App\Models\SearchEvaluation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class JudgeLogs extends Component
{
    use WithPagination;

    public const int PER_PAGE = 20;

    /**
     * When set, shows only this judge's logs (per-judge mode).
     */
    public ?Judge $judge = null;

    public string $filterStatus = 'all';
    public int $filterJudgeId = 0;
    public int $filterEvaluationId = 0;
    public string $date = '';

    public function mount($judge = null): void
    {
        if ($judge !== null) {
            // Without type hint, Livewire passes the raw route parameter (ID string), not the model
            if (!$judge instanceof Judge) {
                $judge = Judge::findOrFail($judge);
            }

            abort_unless(
                $judge->team_id === Auth::user()->current_team_id,
                403,
            );

            $this->judge = $judge;
            $this->filterJudgeId = $judge->id;
        }
    }

    public function render(): View
    {
        $teamId = Auth::user()->current_team_id;

        $query = JudgeLog::query();

        if ($this->judge !== null) {
            // Per-judge mode: hard-scope to this judge
            $query->where(JudgeLog::FIELD_JUDGE_ID, $this->judge->id);
        } else {
            // Global mode: scope to team (covers logs from deleted judges via team_id)
            $query->where(JudgeLog::FIELD_TEAM_ID, $teamId);

            if ($this->filterJudgeId > 0) {
                $query->where(JudgeLog::FIELD_JUDGE_ID, $this->filterJudgeId);
            }
        }

        // Evaluation filter
        if ($this->filterEvaluationId > 0) {
            $query->where(JudgeLog::FIELD_SEARCH_EVALUATION_ID, $this->filterEvaluationId);
        }

        // Date range filter
        [$dateFrom, $dateTo] = $this->parseDateRange();
        if ($dateFrom !== null) {
            $query->where(JudgeLog::FIELD_CREATED_AT, '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->where(JudgeLog::FIELD_CREATED_AT, '<=', $dateTo);
        }

        // Compute status counts before applying the status filter so badge numbers
        // stay stable regardless of which tab is active (avoids layout shifts).
        $countSuccessful = (clone $query)->successful()->count();
        $countFailed = (clone $query)->failed()->count();

        // Status filter
        if ($this->filterStatus === 'success') {
            $query->successful();
        } elseif ($this->filterStatus === 'error') {
            $query->failed();
        }

        $logs = $query
            ->with(['judge', 'evaluation'])
            ->orderByDesc(JudgeLog::FIELD_CREATED_AT)
            ->orderByDesc(JudgeLog::FIELD_ID)
            ->paginate(self::PER_PAGE);

        // Judge list for the dropdown (global mode only)
        $judgeOptions = $this->judge === null
            ? Judge::where(Judge::FIELD_TEAM_ID, Auth::user()->current_team_id)
                ->orderBy(Judge::FIELD_NAME)
                ->get([Judge::FIELD_ID, Judge::FIELD_NAME])
            : collect();

        // Evaluation list for the dropdown
        $evaluationIds = JudgeLog::where(JudgeLog::FIELD_TEAM_ID, $teamId)
            ->whereNotNull(JudgeLog::FIELD_SEARCH_EVALUATION_ID)
            ->distinct()
            ->pluck(JudgeLog::FIELD_SEARCH_EVALUATION_ID);

        $evaluationOptions = SearchEvaluation::whereIn(SearchEvaluation::FIELD_ID, $evaluationIds)
            ->orderByDesc(SearchEvaluation::FIELD_ID)
            ->get([SearchEvaluation::FIELD_ID, SearchEvaluation::FIELD_NAME]);

        return view('livewire.pages.judge-logs', [
            'logs' => $logs,
            'judgeOptions' => $judgeOptions,
            'evaluationOptions' => $evaluationOptions,
            'countSuccessful' => $countSuccessful,
            'countFailed' => $countFailed,
        ])->title($this->judge !== null
            ? sprintf('%s Logs', $this->judge->name)
            : 'Judge Logs'
        );
    }

    public function resetFilters(): void
    {
        $this->filterStatus = 'all';
        $this->filterJudgeId = $this->judge !== null ? $this->judge->id : 0;
        $this->filterEvaluationId = 0;
        $this->date = '';
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterJudgeId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterEvaluationId(): void
    {
        $this->resetPage();
    }

    public function updatedDate(): void
    {
        $this->resetPage();
    }

    /**
     * Parse the flatpickr range string ("Jan 1, 2026 - Feb 19, 2026") into Carbon start/end.
     *
     * @return array{Carbon|null, Carbon|null}
     */
    private function parseDateRange(): array
    {
        if ($this->date === '') {
            return [null, null];
        }

        $parts = explode('-', $this->date);

        if (count($parts) === 1) {
            $day = Carbon::parse(trim($parts[0]));
            return [$day->startOfDay(), $day->copy()->endOfDay()];
        }

        return [
            Carbon::parse(trim($parts[0]))->startOfDay(),
            Carbon::parse(trim($parts[1]))->endOfDay(),
        ];
    }
}
