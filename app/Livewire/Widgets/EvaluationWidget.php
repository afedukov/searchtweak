<?php

namespace App\Livewire\Widgets;

use App\Models\SearchEvaluation;
use Illuminate\View\View;

class EvaluationWidget extends BaseWidget
{
    public SearchEvaluation $evaluation;

    public static function getWidgetName(array $data = null): string
    {
        $evaluation = SearchEvaluation::find($data['id']);
        if ($evaluation === null) {
            return 'Evaluation';
        }

        return sprintf('Evaluation: %s', $evaluation->name);
    }

    public static function isRemovable(): bool
    {
        return true;
    }

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.status.changed', $this->evaluation->id) => '$refresh',
            sprintf('echo-private:search-evaluation.%s,.evaluation.feedback.changed', $this->evaluation->id) => '$refresh',
        ];
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
        return view('livewire.widgets.evaluation-widget');
    }
}
