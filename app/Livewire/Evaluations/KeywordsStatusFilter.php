<?php

namespace App\Livewire\Evaluations;

use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class KeywordsStatusFilter extends Component
{
    public SearchEvaluation $evaluation;

    #[Modelable]
    public string $status = 'all';

    public function render(): View
    {
        return view('livewire.evaluations.keywords-status-filter');
    }
}
