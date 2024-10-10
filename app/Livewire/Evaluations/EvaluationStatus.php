<?php

namespace App\Livewire\Evaluations;

use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Component;

class EvaluationStatus extends Component
{
    public SearchEvaluation $evaluation;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.status.changed', $this->evaluation->id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-status', [
            'color' => $this->getColor(),
            'label' => $this->getLabel(),
        ]);
    }

    private function getColor(): string
    {
        if ($this->isFailed()) {
            return 'red';
        }

        return match ($this->evaluation->status) {
            SearchEvaluation::STATUS_PENDING => 'red',
            SearchEvaluation::STATUS_ACTIVE => 'green',
            SearchEvaluation::STATUS_FINISHED => 'blue',
            default => 'red',
        };
    }

    private function getLabel(): string
    {
        if ($this->isFailed()) {
            return 'Failed';
        }

        return $this->evaluation->status_label;
    }

    private function isFailed(): bool
    {
        return $this->evaluation->isActive() && $this->evaluation->successful_keywords === 0;
    }
}
