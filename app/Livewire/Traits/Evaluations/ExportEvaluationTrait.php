<?php

namespace App\Livewire\Traits\Evaluations;

use App\Actions\Evaluations\ExportSearchEvaluation;
use App\Models\SearchEvaluation;
use Symfony\Component\HttpFoundation\Response;
use Toaster;

trait ExportEvaluationTrait
{
    public function exportEvaluation(SearchEvaluation $evaluation, ExportSearchEvaluation $action): Response
    {
        try {
            return $action->export($evaluation);
        } catch (\RuntimeException $e) {
            Toaster::error($e->getMessage());

            return redirect()->back();
        }
    }
}
