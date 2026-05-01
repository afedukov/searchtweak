<?php

namespace App\Livewire\Traits\Evaluations;

use App\Jobs\Evaluations\PauseEvaluationJob;
use App\Jobs\Evaluations\RerunFailedKeywordsJob;
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

    public function rerunFailedKeywords(SearchEvaluation $evaluation): void
    {
        try {
            Gate::authorize('rerunFailed', $evaluation);
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());

            return;
        }

        if ($evaluation->changes_blocked) {
            Toaster::error('Evaluation changes are blocked.');

            return;
        }

        if (!$evaluation->hasStarted()) {
            Toaster::error('Failed to rerun failed keywords: evaluation has not started');

            return;
        }

        if (!$evaluation->isActive() && !$evaluation->isPending()) {
            Toaster::error('Failed to rerun failed keywords: evaluation must be active or pending');

            return;
        }

        if ($evaluation->failed_keywords === 0) {
            Toaster::error('Failed to rerun failed keywords: evaluation has no failed keywords');

            return;
        }

        $evaluation->blockChanges();

        RerunFailedKeywordsJob::dispatch($evaluation->id);
    }
}
