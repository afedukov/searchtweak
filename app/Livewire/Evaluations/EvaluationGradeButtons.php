<?php

namespace App\Livewire\Evaluations;

use App\Actions\Evaluations\GradeSearchSnapshot;
use App\Actions\Evaluations\ResetSearchSnapshot;
use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use App\Models\SearchSnapshot;
use App\Models\UserFeedback;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Toaster;

class EvaluationGradeButtons extends Component
{
    public SearchEvaluation $evaluation;
    public SearchSnapshot $snapshot;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%d,.evaluation.feedback.changed', $this->evaluation->id) => '$refresh',
        ];
    }

    public function render(): View
    {
        $feedbacks = $this->snapshot->feedbacks
            ->filter(fn (UserFeedback $feedback) =>
                ($feedback->grade !== null) ||
                ($feedback->user_id !== null && !$feedback->isAssignmentExpired())
            );

        return view('livewire.evaluations.evaluation-grade-buttons', [
            'feedbacks' => $feedbacks,
            'grade' => $feedbacks->where(UserFeedback::FIELD_USER_ID, Auth::id())->first()?->grade,
        ]);
    }

    public function grade(int $snapshotId, int $grade, GradeSearchSnapshot $action): void
    {
        try {
            $action->grade($this->getSnapshot($snapshotId), Auth::user(), $grade);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }
    }

    public function resetGrade(int $snapshotId, ResetSearchSnapshot $action): void
    {
        try {
            $action->reset($this->getSnapshot($snapshotId), Auth::user());
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }
    }

    protected function getSnapshot(int $snapshotId): SearchSnapshot
    {
        return $this->evaluation->keywords
            ->flatMap(fn (EvaluationKeyword $keyword) => $keyword->snapshots)
            ->firstWhere(SearchSnapshot::FIELD_ID, $snapshotId) ?? throw new \InvalidArgumentException('Snapshot not found');
    }
}
