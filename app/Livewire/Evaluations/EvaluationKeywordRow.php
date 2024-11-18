<?php

namespace App\Livewire\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Component;

class EvaluationKeywordRow extends Component
{
    public SearchEvaluation $evaluation;

    public EvaluationKeyword $keyword;

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-keyword-row');
    }
}
