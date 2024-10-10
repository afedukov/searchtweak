<?php

namespace App\Actions\Evaluations;

use App\Models\SearchEvaluation;

class DeleteSearchEvaluation
{
    /**
     * @param SearchEvaluation $evaluation
     *
     * @return void
     */
    public function delete(SearchEvaluation $evaluation): void
    {
        if (!$evaluation->isDeletable()) {
            throw new \RuntimeException('Evaluation cannot be deleted.');
        }

        $evaluation->delete();
    }
}
