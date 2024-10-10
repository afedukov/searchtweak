<?php

namespace App\Actions\Models;

use App\Livewire\Forms\ModelForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CreateSearchModel
{
    public function create(ModelForm $form): void
    {
        Gate::authorize('create-model', Auth::user()->currentTeam);

        $form->store();
    }
}
