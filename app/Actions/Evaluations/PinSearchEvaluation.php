<?php

namespace App\Actions\Evaluations;

use App\Models\SearchEvaluation;

class PinSearchEvaluation
{
    /**
     * @param SearchEvaluation $evaluation
     * @param bool $pinned
     *
     * @return void
     */
    public function pin(SearchEvaluation $evaluation, bool $pinned): void
    {
        if ($pinned === true && !$evaluation->isPinnable()) {
            throw new \RuntimeException('Failed to pin evaluation: evaluation is not pinnable');
        }

        if ($pinned === false && !$evaluation->isUnpinnable()) {
            throw new \RuntimeException('Failed to un-pin evaluation: evaluation is not unpinnable');
        }

        $evaluation->pinned = $pinned;
        $evaluation->save();
    }
}
