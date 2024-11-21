<?php

namespace App\Livewire\Evaluations;

use App\Actions\Evaluations\FinishSearchEvaluation;
use App\Models\SearchEvaluation;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Toaster;

class EvaluationFinishButton extends Component
{
    public SearchEvaluation $evaluation;

    public bool $confirmingEvaluationFinish = false;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.status.changed', $this->evaluation->id) => '$refresh',
            sprintf('echo-private:search-evaluation.%s,.evaluation.progress.changed', $this->evaluation->id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-finish-button');
    }

    public function finishEvaluation(FinishSearchEvaluation $action): void
    {
        try {
            Gate::authorize('finish', $this->evaluation);

            $action->finish($this->evaluation, false);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());

            return;
        } finally {
            $this->confirmingEvaluationFinish = false;
        }
    }
}
