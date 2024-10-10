<?php

namespace App\Actions\Evaluations;

use App\Livewire\Forms\EvaluationForm;
use Illuminate\Support\Facades\Gate;

class UpdateSearchEvaluation
{
    public function update(EvaluationForm $form): void
    {
        Gate::authorize('update', $form->evaluation);

        $form->update();
    }
}
