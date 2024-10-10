<?php

namespace App\Actions\Endpoints;

use App\Models\SearchEndpoint;
use Illuminate\Support\Facades\Gate;

class DeleteSearchEndpoint
{
    public function delete(SearchEndpoint $searchEndpoint): void
    {
        Gate::authorize('delete', $searchEndpoint);

        $searchEndpoint->delete();
    }
}
