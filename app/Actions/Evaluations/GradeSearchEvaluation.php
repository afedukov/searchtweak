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

        if ($feedback->user_id === $user->id && $feedback->isAssignmentExpired()) {
            throw new \RuntimeException('Snapshot assignment expired');
        }

        if ($feedback->grade !== null) {
            throw new \RuntimeException('Snapshot is already graded');
        }

        $feedback->grade = $grade;
        $feedback->save();
    }
}
