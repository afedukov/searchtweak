<?php

namespace App\Livewire;

use App\Actions\Evaluations\GradeSearchEvaluation;
use App\Models\SearchEvaluation;
use App\Models\UserFeedback;
use App\Services\Evaluations\UserFeedbackService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Toaster;

class GiveFeedback extends Component
{
    public ?SearchEvaluation $evaluation = null;

    public ?UserFeedback $feedback = null;

    public function mount(?int $evaluationId = null): void
    {
        if ($evaluationId === null) {
            Gate::authorize('giveFeedbackGlobalPool', SearchEvaluation::class);
        } else {
            $this->evaluation = SearchEvaluation::findOrFail($evaluationId);

            Gate::authorize('giveFeedbackEvaluationPool', $this->evaluation);
        }

        if ($this->evaluation === null || $this->evaluation->isActive()) {
            $this->fetchFeedback();
        }
    }

    private function fetchFeedback(): void
    {
        $this->feedback = app(UserFeedbackService::class)->fetch(Auth::user(), $this->evaluation);
    }

    public function render(): View
    {
        return view('livewire.pages.give-feedback')
            ->title($this->evaluation ? sprintf('Give Feedback: %s', $this->evaluation->name) : 'Give Feedback');
    }

    public function grade(UserFeedback $feedback, int $grade, GradeSearchEvaluation $action): void
    {
        try {
            $action->grade($feedback, Auth::user(), $grade);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }

        $this->fetchFeedback();
        $this->dispatch('$refresh');
    }
}
