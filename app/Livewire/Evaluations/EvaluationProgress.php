<?php

namespace App\Livewire\Evaluations;

use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Component;

class EvaluationProgress extends Component
{
    public SearchEvaluation $evaluation;

    public string $class = '';

    public bool $link = false;
    public bool $total = false;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.progress.changed', $this->evaluation->id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-progress', [
            'progress' => $this->total ? $this->evaluation->getProgressTotal() : '',
        ]);
    }
}
