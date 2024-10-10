<?php

namespace App\Actions\Evaluations;

use App\Livewire\Forms\EvaluationForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CreateSearchEvaluation
{
    /**
     * @param EvaluationForm $form
     *
     * @return void
     */
    public function create(EvaluationForm $form): void
    {
        Gate::authorize('create-evaluation', Auth::user()->currentTeam);

        $form->store();
    }
}
