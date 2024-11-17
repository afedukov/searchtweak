<?php

namespace App\Livewire\Evaluations;

use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Component;

class BaselineEvaluation extends Component
{
    public ?SearchEvaluation $baseline = null;

    protected function getListeners(): array
    {
        if ($this->baseline === null) {
            return [];
        }

        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.metric.changed', $this->baseline->id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.evaluations.baseline-evaluation');
    }
}
