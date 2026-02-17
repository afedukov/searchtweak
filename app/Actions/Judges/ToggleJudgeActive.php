<?php

namespace App\Actions\Judges;

use App\Models\Judge;
use Illuminate\Support\Facades\Gate;

class ToggleJudgeActive
{
    public function toggle(Judge $judge): void
    {
        Gate::authorize('toggle', $judge);

        if ($judge->isActive()) {
            $judge->touch(Judge::FIELD_ARCHIVED_AT);
        } else {
            $judge->update([
                Judge::FIELD_ARCHIVED_AT => null,
            ]);
        }
    }
}
