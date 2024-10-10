<?php

namespace App\Livewire\Evaluations;

use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Component;

class EvaluationKeywordsCount extends Component
{
    public SearchEvaluation $evaluation;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.status.changed', $this->evaluation->id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-keywords-count');
    }
}
