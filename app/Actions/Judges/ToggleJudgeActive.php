<?php

namespace App\Actions\Judges;

use App\Jobs\Evaluations\ProcessJudgeEvaluationJob;
use App\Models\Judge;
use App\Models\SearchEvaluation;
use Illuminate\Support\Facades\Gate;

class ToggleJudgeActive
{
    public function toggle(Judge $judge): void
    {
        Gate::authorize('toggle', $judge);

        if ($judge->isActive()) {
            // Deactivating: running job will exclude this judge on next cycle
            $judge->touch(Judge::FIELD_ARCHIVED_AT);
        } else {
            // Activating: clear archive and check for work
            $judge->update([
                Judge::FIELD_ARCHIVED_AT => null,
            ]);

            $this->dispatchForActiveEvaluations($judge);
        }
    }

    private function dispatchForActiveEvaluations(Judge $judge): void
    {
        $judge->load('tags');

        SearchEvaluation::team($judge->team_id)
            ->active()
            ->with('tags')
            ->each(function (SearchEvaluation $evaluation) use ($judge) {
                if (Judge::matchesEvaluation($judge, $evaluation)) {
                    // ShouldBeUnique prevents duplicate jobs
                    ProcessJudgeEvaluationJob::dispatch($evaluation->id);
                }
            });
    }
}
