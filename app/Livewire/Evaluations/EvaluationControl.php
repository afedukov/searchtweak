<?php

namespace App\Livewire\Evaluations;

use App\Livewire\Traits\Evaluations\ControlEvaluationTrait;
use App\Models\SearchEvaluation;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class EvaluationControl extends Component
{
    use ControlEvaluationTrait;

    public SearchEvaluation $evaluation;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.status.changed', $this->evaluation->id) => '$refresh',
            sprintf('echo-private:search-evaluation.%s,.evaluation.keyword-counts.changed', $this->evaluation->id) => '$refresh',
            sprintf('echo-private:search-evaluation.%s,.evaluation.changes-block.changed', $this->evaluation->id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.evaluations.evaluation-control');
    }

    public function canRerunFailed(): bool
    {
        return !$this->evaluation->changes_blocked
            && $this->evaluation->failed_keywords > 0
            && $this->evaluation->hasStarted()
            && ($this->evaluation->isActive() || $this->evaluation->isPending())
            && Gate::check('rerunFailed', $this->evaluation);
    }
}
