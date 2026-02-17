<?php

namespace App\Actions\Judges;

use App\Livewire\Forms\JudgeForm;
use Illuminate\Support\Facades\Gate;

class UpdateJudge
{
    public function update(JudgeForm $form): void
    {
        Gate::authorize('update', $form->judge);

        $form->update();
    }
}
