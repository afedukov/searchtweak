<?php

namespace App\Livewire\Evaluations;

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Component;

class KeywordsSelectMetrics extends Component
{
    public array $metrics = [];

    public SearchEvaluation $evaluation;

    public function mount(): void
    {
        $this->metrics = $this->getMetrics();
    }

    public function render(): View
    {
        return view('livewire.evaluations.keywords-select-metrics');
    }

    private function getMetrics(): array
    {
        return $this->evaluation
            ->metrics
            ->mapWithKeys(function (EvaluationMetric $metric) {
                return [$metric->id => $metric->getFullyQualifiedName()];
            })
            ->all();
    }
}
