<?php

namespace App\Actions\Endpoints;

use App\Models\SearchEndpoint;
use Illuminate\Support\Facades\Gate;

class ToggleSearchEndpointActive
{
    public function toggle(SearchEndpoint $searchEndpoint): void
    {
        Gate::authorize('toggle', $searchEndpoint);

        if ($searchEndpoint->isActive()) {
            $searchEndpoint->touch(SearchEndpoint::FIELD_ARCHIVED_AT);
        } else {
            $searchEndpoint->update([
                SearchEndpoint::FIELD_ARCHIVED_AT => null,
            ]);
        }
    }
}
