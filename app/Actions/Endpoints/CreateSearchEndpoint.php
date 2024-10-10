<?php

namespace App\Actions\Endpoints;

use App\Livewire\Forms\EndpointForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CreateSearchEndpoint
{
    public function create(EndpointForm $form): void
    {
        Gate::authorize('create-endpoint', Auth::user()->currentTeam);

        $form->store();
    }
}
