<?php

namespace App\Livewire\Traits\Evaluations;

use App\Actions\Evaluations\BaselineSearchEvaluation;
use App\Models\SearchEvaluation;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;

trait BaselineEvaluationTrait
{
    public function baseline(SearchEvaluation $evaluation, bool $baseline, BaselineSearchEvaluation $action): void
    {
        try {
            Gate::authorize('baseline', $evaluation);

            $action->baseline($evaluation, $baseline);

            Toaster::success(sprintf('Evaluation %s as baseline.', $baseline ? 'set' : 'unset'));

            $this->dispatch('baseline-reset');
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }
    }

    #[On('baseline-reset')]
    public function refresh(): void
    {
        $this->dispatch('$refresh');
    }
}
