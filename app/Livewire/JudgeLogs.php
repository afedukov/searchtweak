<?php

namespace App\Livewire;

use App\Models\Judge;
use App\Models\JudgeLog;
use App\Models\SearchEvaluation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $query = $this->getFilteredLogsQuery(applyStatusFilter: false);

        // Compute status counts before applying the status filter so badge numbers
        // stay stable regardless of which tab is active (avoids layout shifts).
        $countSuccessful = (clone $query)->successful()->count();
        $countFailed = (clone $query)->failed()->count();

        $logs = $this->getFilteredLogsQuery()
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

    public function exportJsonl(): StreamedResponse
    {
        $fileName = sprintf('judge-logs_%s.jsonl', Carbon::now()->format('Y-m-d_H-i-s'));

        $query = $this->getFilteredLogsQuery()
            ->with(['judge', 'evaluation'])
            ->orderByDesc(JudgeLog::FIELD_CREATED_AT)
            ->orderByDesc(JudgeLog::FIELD_ID);

        return response()->streamDownload(function () use ($query): void {
            $stream = fopen('php://output', 'w');

            foreach ($query->cursor() as $log) {
                /** @var JudgeLog $log */
                $payload = [
                    JudgeLog::FIELD_ID => $log->id,
                    JudgeLog::FIELD_JUDGE_ID => $log->judge_id,
                    'judge_name' => $log->judge?->name,
                    JudgeLog::FIELD_TEAM_ID => $log->team_id,
                    JudgeLog::FIELD_SEARCH_EVALUATION_ID => $log->search_evaluation_id,
                    'evaluation_name' => $log->evaluation?->name,
                    JudgeLog::FIELD_PROVIDER => $log->provider,
                    JudgeLog::FIELD_MODEL => $log->model,
                    'status' => $log->isSuccessful() ? 'success' : 'error',
                    JudgeLog::FIELD_HTTP_STATUS_CODE => $log->http_status_code,
                    JudgeLog::FIELD_REQUEST_URL => $log->request_url,
                    JudgeLog::FIELD_REQUEST_BODY => $log->request_body,
                    JudgeLog::FIELD_RESPONSE_BODY => $log->response_body,
                    JudgeLog::FIELD_ERROR_MESSAGE => $log->error_message,
                    JudgeLog::FIELD_LATENCY_MS => $log->latency_ms,
                    JudgeLog::FIELD_PROMPT_TOKENS => $log->prompt_tokens,
                    JudgeLog::FIELD_COMPLETION_TOKENS => $log->completion_tokens,
                    JudgeLog::FIELD_TOTAL_TOKENS => $log->total_tokens,
                    JudgeLog::FIELD_BATCH_SIZE => $log->batch_size,
                    JudgeLog::FIELD_SCALE_TYPE => $log->scale_type,
                    JudgeLog::FIELD_CREATED_AT => $log->created_at?->toIso8601String(),
                    JudgeLog::FIELD_UPDATED_AT => $log->updated_at?->toIso8601String(),
                ];

                fwrite($stream, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
            }

            fclose($stream);
        }, $fileName, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
        ]);
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

    private function getFilteredLogsQuery(bool $applyStatusFilter = true): Builder
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

        if ($this->filterEvaluationId > 0) {
            $query->where(JudgeLog::FIELD_SEARCH_EVALUATION_ID, $this->filterEvaluationId);
        }

        [$dateFrom, $dateTo] = $this->parseDateRange();
        if ($dateFrom !== null) {
            $query->where(JudgeLog::FIELD_CREATED_AT, '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->where(JudgeLog::FIELD_CREATED_AT, '<=', $dateTo);
        }

        if ($applyStatusFilter) {
            if ($this->filterStatus === 'success') {
                $query->successful();
            } elseif ($this->filterStatus === 'error') {
                $query->failed();
            }
        }

        return $query;
    }
}
