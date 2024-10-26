<?php

namespace App\Livewire\Evaluations;

use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Component;

class EvaluationArchivedBadge extends Component
{
    public SearchEvaluation $evaluation;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.archived.changed', $this->evaluation->id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-archived-badge', [
            'archived' => $this->evaluation->archived,
        ]);
    }
}
