<?php

namespace App\Livewire\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\KeywordMetric;
use Illuminate\View\View;
use Livewire\Component;

class EvaluationKeywordMetric extends Component
{
    public EvaluationKeyword $keyword;
    public EvaluationMetric $metric;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%d,.evaluation.feedback.changed', $this->keyword->search_evaluation_id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-keyword-metric', [
            'scorer' => $this->metric->getScorer(),
            'value' => $this->metric->keywordMetrics->firstWhere(KeywordMetric::FIELD_EVALUATION_KEYWORD_ID, $this->keyword->id)?->value ?? null,
        ]);
    }
}
