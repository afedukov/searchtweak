<?php

namespace App\Actions\Judges;

use App\Models\Judge;
use Illuminate\Support\Facades\Gate;

class DeleteJudge
{
    public function delete(Judge $judge): void
    {
        Gate::authorize('delete', $judge);

        $judge->delete();
    }
}
