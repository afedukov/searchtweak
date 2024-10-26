<?php

namespace App\Livewire\Traits\Evaluations;

use App\Actions\Evaluations\PinSearchEvaluation;
use App\Models\SearchEvaluation;
use Illuminate\Support\Facades\Gate;
use Masmerise\Toaster\Toaster;

trait PinEvaluationTrait
{
    public function pin(SearchEvaluation $evaluation, bool $pinned, PinSearchEvaluation $action): void
    {
        try {
            Gate::authorize('pin', $evaluation);

            $action->pin($evaluation, $pinned);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }
    }
}
