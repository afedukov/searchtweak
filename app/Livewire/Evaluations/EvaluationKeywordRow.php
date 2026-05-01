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

    /**
     * @var array<string, float|null>
     */
    public array $baselineValues = [];

    public bool $expanded = false;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.status.changed', $this->evaluation->id) => '$refresh',
            sprintf('echo-private:search-evaluation.%s,.evaluation.keywords.changed', $this->evaluation->id) => 'refreshKeyword',
        ];
    }

    public function refreshKeyword(): void
    {
        $this->keyword = $this->keyword->refresh();
        $this->evaluation = $this->evaluation->refresh()->load('metrics');
    }

    public function toggleExpanded(): void
    {
        $this->expanded = !$this->expanded;
    }

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-keyword-row');
    }
}
