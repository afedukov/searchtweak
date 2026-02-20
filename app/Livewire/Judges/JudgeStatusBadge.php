<?php

namespace App\Livewire\Judges;

use App\Models\Judge;
use App\Models\JudgeLog;
use App\Models\UserFeedback;
use Illuminate\View\View;
use Livewire\Component;

class JudgeStatusBadge extends Component
{
    public int $judgeId;
    public int $teamId;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:team.%d,.evaluation.feedback.changed', $this->teamId) => '$refresh',
            sprintf('echo-private:team.%d,.JudgeCreated', $this->teamId) => '$refresh',
            sprintf('echo-private:team.%d,.JudgeUpdated', $this->teamId) => '$refresh',
            sprintf('echo-private:team.%d,.JudgeDeleted', $this->teamId) => '$refresh',
        ];
    }

    public function render(): View
    {
        $status = $this->getStatus();
        $hasError = $this->hasError();

        return view('livewire.judges.judge-status-badge', [
            'status' => $status,
            'label' => $status === null ? null : $this->getLabel($status),
            'hasError' => $hasError,
            'judgeLogsUrl' => route('judge.logs', $this->judgeId),
        ]);
    }

    private function getStatus(): ?string
    {
        $judge = Judge::query()->find($this->judgeId);

        if ($judge === null || !$judge->isActive()) {
            return null;
        }

        if ($this->hasClaimedUngradedFeedback($judge->id)) {
            return 'working';
        }

        return 'waiting';
    }

    private function hasClaimedUngradedFeedback(int $judgeId): bool
    {
        return UserFeedback::query()
            ->where(UserFeedback::FIELD_JUDGE_ID, $judgeId)
            ->whereNull(UserFeedback::FIELD_GRADE)
            ->whereHas('snapshot.keyword.evaluation', fn ($query) => $query->active())
            ->whereHas('snapshot.keyword.evaluation.model', fn ($query) => $query->where('team_id', $this->teamId))
            ->exists();
    }

    private function getLabel(string $status): string
    {
        return match ($status) {
            'working' => 'Working',
            default => 'Waiting',
        };
    }

    private function hasError(): bool
    {
        $lastLog = JudgeLog::query()
            ->where(JudgeLog::FIELD_TEAM_ID, $this->teamId)
            ->where(JudgeLog::FIELD_JUDGE_ID, $this->judgeId)
            ->latest(JudgeLog::FIELD_ID)
            ->first();

        if ($lastLog === null) {
            return false;
        }

        return !$lastLog->isSuccessful();
    }
}
