<?php

namespace App\Livewire\Evaluations;

use App\Models\EvaluationKeyword;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class EvaluationKeywordCountBadge extends Component
{
    public EvaluationKeyword $keyword;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%d,.evaluation.feedback.changed', $this->keyword->search_evaluation_id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-keyword-count-badge', [
            'count' => $this->keyword->getUngradedSnapshotsCount(Auth::id())
        ]);
    }
}
