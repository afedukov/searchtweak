<?php

namespace App\Livewire\Traits\Evaluations;

use App\Actions\Evaluations\ArchiveSearchEvaluation;
use App\Models\SearchEvaluation;
use Illuminate\Support\Facades\Gate;
use Masmerise\Toaster\Toaster;

trait ArchiveEvaluationTrait
{
    public function archive(SearchEvaluation $evaluation, bool $archived, ArchiveSearchEvaluation $action): void
    {
        try {
            Gate::authorize('archive', $evaluation);

            $action->archive($evaluation, $archived);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }
    }
}
