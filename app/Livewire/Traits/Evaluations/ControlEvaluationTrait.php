<?php

namespace App\Livewire\Traits\Evaluations;

use App\Jobs\Evaluations\PauseEvaluationJob;
use App\Jobs\Evaluations\StartEvaluationJob;
use App\Models\SearchEvaluation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Masmerise\Toaster\Toaster;

trait ControlEvaluationTrait
{
    public function start(SearchEvaluation $evaluation): void
    {
        try {
            Gate::authorize('start', $evaluation);
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());

            return;
        }

        $evaluation->blockChanges();

        StartEvaluationJob::dispatch($evaluation->id);
    }

    public function pause(SearchEvaluation $evaluation): void
    {
        try {
            Gate::authorize('pause', $evaluation);
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());

            return;
        }

        $evaluation->blockChanges();

        PauseEvaluationJob::dispatch($evaluation->id);
    }
}
