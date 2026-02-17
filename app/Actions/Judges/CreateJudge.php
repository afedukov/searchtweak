<?php

namespace App\Actions\Judges;

use App\Livewire\Forms\JudgeForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CreateJudge
{
    public function create(JudgeForm $form): void
    {
        Gate::authorize('create-judge', Auth::user()->currentTeam);

        $form->store();
    }
}
