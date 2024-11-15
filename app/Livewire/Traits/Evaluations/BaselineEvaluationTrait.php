<?php

namespace App\Livewire\Traits\Evaluations;

use App\Actions\Evaluations\BaselineSearchEvaluation;
use App\Models\SearchEvaluation;
use Illuminate\Support\Facades\Gate;
use Masmerise\Toaster\Toaster;

trait BaselineEvaluationTrait
{
    public ?SearchEvaluation $baseline = null;

    public function setBaseline(SearchEvaluation $evaluation, bool $baseline, BaselineSearchEvaluation $action): void
    {
        try {
            Gate::authorize('baseline', $evaluation);

            $this->baseline = $action->baseline($evaluation, $baseline);

            Toaster::success(sprintf('Evaluation %s as baseline.', $baseline ? 'set' : 'unset'));
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }
    }
}
