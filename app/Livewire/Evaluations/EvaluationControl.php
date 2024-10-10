<?php

namespace App\Livewire\Evaluations;

use App\Livewire\Traits\Evaluations\ControlEvaluationTrait;
use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Component;

class EvaluationControl extends Component
{
    use ControlEvaluationTrait;

    public SearchEvaluation $evaluation;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.status.changed', $this->evaluation->id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-control');
    }
}
