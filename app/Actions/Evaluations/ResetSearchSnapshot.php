<?php

namespace App\Actions\Evaluations;

use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Support\Facades\Gate;

class ResetSearchSnapshot
{
    public function reset(SearchSnapshot $snapshot, User $user): void
    {
        Gate::forUser($user)->authorize('resetSnapshot', $snapshot->keyword->evaluation);

        if ($snapshot->keyword->evaluation->isFinished()) {
            throw new \RuntimeException('Evaluation is finished');
        }

        $feedback = $snapshot->feedbacks
            ->whereNotNull(UserFeedback::FIELD_GRADE)
            ->where(UserFeedback::FIELD_USER_ID, $user->id)
            ->first();

        if ($feedback instanceof UserFeedback) {
            $feedback->user_id = null;
            $feedback->grade = null;
            $feedback->save();
        }
    }
}
