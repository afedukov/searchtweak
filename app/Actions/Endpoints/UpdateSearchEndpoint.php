<?php

namespace App\Actions\Endpoints;

use App\Livewire\Forms\EndpointForm;
use Illuminate\Support\Facades\Gate;

class UpdateSearchEndpoint
{
    public function update(EndpointForm $form): void
    {
        Gate::authorize('update', $form->endpoint);

        $form->update();
    }
}
