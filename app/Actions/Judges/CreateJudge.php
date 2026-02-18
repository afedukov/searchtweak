<?php

namespace App\Actions\Judges;

use App\Jobs\Evaluations\ProcessJudgeEvaluationJob;
use App\Livewire\Forms\JudgeForm;
use App\Models\Judge;
use App\Models\SearchEvaluation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CreateJudge
{
    public function create(JudgeForm $form): void
    {
        Gate::authorize('create-judge', Auth::user()->currentTeam);

        $form->store();

        $this->dispatchJudgeJobsIfNeeded();
    }

    private function dispatchJudgeJobsIfNeeded(): void
    {
        $team = Auth::user()->currentTeam;
        $judge = $team->judges()->latest(Judge::FIELD_ID)->with('tags')->first();

        if ($judge === null || !$judge->isActive()) {
            return;
        }

        SearchEvaluation::team($team->id)
            ->active()
            ->with('tags')
            ->each(function (SearchEvaluation $evaluation) use ($judge) {
                if (Judge::matchesEvaluation($judge, $evaluation)) {
                    ProcessJudgeEvaluationJob::dispatch($evaluation->id);
                }
            });
    }
}
