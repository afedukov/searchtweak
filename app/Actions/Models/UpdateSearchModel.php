<?php

namespace App\Actions\Models;

use App\Livewire\Forms\ModelForm;
use Illuminate\Support\Facades\Gate;

class UpdateSearchModel
{
    public function update(ModelForm $form): void
    {
        Gate::authorize('update', $form->model);

        $form->update();
    }
}
