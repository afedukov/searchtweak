<?php

namespace App\Actions\Evaluations;

use App\Models\SearchEvaluation;

class ArchiveSearchEvaluation
{
    /**
     * @param SearchEvaluation $evaluation
     * @param bool $archived
     *
     * @return void
     */
    public function archive(SearchEvaluation $evaluation, bool $archived): void
    {
        if ($archived === true && !$evaluation->isArchivable()) {
            throw new \RuntimeException('Failed to archive evaluation: evaluation is not archivable');
        }

        if ($archived === false && !$evaluation->isUnarchivable()) {
            throw new \RuntimeException('Failed to un-archive evaluation: evaluation is not unarchivable');
        }

        $evaluation->archived = $archived;
        $evaluation->save();
    }
}
