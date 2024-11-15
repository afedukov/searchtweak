<?php

namespace App\Actions\Evaluations;

use App\Models\SearchEvaluation;
use Illuminate\Support\Facades\Auth;

class BaselineSearchEvaluation
{
    /**
     * @param SearchEvaluation $evaluation
     * @param bool $baseline
     *
     * @return SearchEvaluation|null
     */
    public function baseline(SearchEvaluation $evaluation, bool $baseline): ?SearchEvaluation
    {
        if ($baseline === true && !$evaluation->isBaselineable()) {
            throw new \RuntimeException('Failed to set evaluation as baseline');
        }

        if ($baseline === false && !$evaluation->isUnbaselineable()) {
            throw new \RuntimeException('Failed to unset evaluation as baseline');
        }

        Auth::user()->currentTeam->baseline_evaluation_id = $baseline ? $evaluation->id : null;
        Auth::user()->currentTeam->save();

        return $baseline ? $evaluation : null;
    }
}
