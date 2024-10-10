<?php

namespace App\Livewire\Widgets;

use App\Models\SearchEvaluation;
use Illuminate\View\View;

class EvaluationProgressWidget extends BaseWidget
{
    public SearchEvaluation $evaluation;

    public static function getWidgetName(array $data = null): string
    {
        $evaluation = SearchEvaluation::find($data['id']);
        if ($evaluation === null) {
            return 'Evaluation Progress';
        }

        return sprintf('Progress: %s', $evaluation->name);
    }

    public static function isRemovable(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $evaluation = SearchEvaluation::find($this->widget['settings']['id']);
        if ($evaluation === null) {
            $this->skipRender();
            return;
        }

        $this->evaluation = $evaluation;
    }

    public function render(): View
    {
        return view('livewire.widgets.evaluation-progress-widget');
    }
}
