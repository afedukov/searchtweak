<?php

namespace App\Livewire\Judges;

use App\Models\UserFeedback;
use Illuminate\View\View;
use Livewire\Component;

class JudgePairsJudgedCount extends Component
{
    public int $judgeId;
    public int $teamId;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:team.%d,.evaluation.feedback.changed', $this->teamId) => '$refresh',
        ];
    }

    public function render(): View
    {
        $count = UserFeedback::query()
            ->where(UserFeedback::FIELD_JUDGE_ID, $this->judgeId)
            ->whereNotNull(UserFeedback::FIELD_GRADE)
            ->whereHas('snapshot.keyword.evaluation.model', fn ($query) => $query->where('team_id', $this->teamId))
            ->count();

        return view('livewire.judges.judge-pairs-judged-count', [
            'count' => $count,
        ]);
    }
}
