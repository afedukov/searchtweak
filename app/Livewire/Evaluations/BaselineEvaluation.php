<?php

namespace App\Livewire\Evaluations;

use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Component;

class BaselineEvaluation extends Component
{
    public ?SearchEvaluation $baseline = null;

    public function render(): View
    {
        return view('livewire.evaluations.baseline-evaluation');
    }
}
