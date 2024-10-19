<?php

namespace App\Actions\Evaluations;

use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Support\Facades\Gate;

class GradeSearchEvaluation
{
    public function grade(UserFeedback $feedback, User $user, int $grade): void
    {
        Gate::forUser($user)->authorize('giveFeedback', $feedback->snapshot->keyword->evaluation);

        if (!$feedback->snapshot->keyword->evaluation->isActive()) {
            throw new \RuntimeException('Evaluation is not active');
        }

        $feedback->refresh();

        if ($feedback->user_id !== $user->id) {
            throw new \RuntimeException('Snapshot assigned to another user');
        }

        if ($feedback->grade === null && $feedback->isAssignmentExpired()) {
            throw new \RuntimeException('Snapshot assignment expired');
        }

        $feedback->grade = $grade;
        $feedback->save();
    }
}
