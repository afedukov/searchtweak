<?php

namespace App\Actions\Evaluations;

use App\Models\SearchEvaluation;

class PauseSearchEvaluation
{
    /**
     * @param SearchEvaluation $evaluation
     *
     * @return void
     */
    public function pause(SearchEvaluation $evaluation): void
    {
        if (!$evaluation->isActive()) {
            throw new \RuntimeException('Failed to pause evaluation: evaluation is not active');
        }

        $evaluation->status = SearchEvaluation::STATUS_PENDING;
        $evaluation->save();
    }
}
