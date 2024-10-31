<?php

namespace App\Livewire\Evaluations;

use App\Livewire\Traits\Evaluations\BaselineEvaluationTrait;
use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Component;

class BaselineEvaluation extends Component
{
    use BaselineEvaluationTrait;

    public ?SearchEvaluation $evaluation;

    public function mount(): void
    {
        if ($this->evaluation === null) {
            $this->skipRender();
        }
    }

    public function render(): View
    {
        return view('livewire.evaluations.baseline-evaluation');
    }
}
