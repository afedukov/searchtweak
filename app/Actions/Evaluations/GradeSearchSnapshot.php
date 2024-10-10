<?php

namespace App\Actions\Evaluations;

use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Support\Facades\Gate;

class GradeSearchSnapshot
{
    public function grade(SearchSnapshot $snapshot, User $user, int $grade): void
    {
        Gate::forUser($user)->authorize('gradeSnapshot', $snapshot->keyword->evaluation);

        if (!$snapshot->keyword->evaluation->isActive()) {
            throw new \RuntimeException('Evaluation is not active');
        }

        /** @var UserFeedback|null $feedback */
        $feedback = $this->getOwnGradedFeedback($snapshot, $user) ??
            $this->getOwnNotGradedFeedback($snapshot, $user) ??
            $this->getNotAssignedNotGradedFeedback($snapshot) ??
            $this->getAssignedNotGradedFeedback($snapshot) ??
            $snapshot->feedbacks->first();

        $feedback->user_id = $user->id;
        $feedback->grade = $grade;
        $feedback->save();
    }

    private function getOwnGradedFeedback(SearchSnapshot $snapshot, User $user): ?UserFeedback
    {
        return $snapshot->feedbacks
            ->whereNotNull(UserFeedback::FIELD_GRADE)
            ->where(UserFeedback::FIELD_USER_ID, $user->id)
            ->first();
    }

    private function getOwnNotGradedFeedback(SearchSnapshot $snapshot, User $user): ?UserFeedback
    {
        return $snapshot->feedbacks
            ->filter(fn (UserFeedback $feedback) =>
                $feedback->grade === null && $feedback->user_id === $user->id && !$feedback->isAssignmentExpired()
            )
            ->first();
    }

    private function getNotAssignedNotGradedFeedback(SearchSnapshot $snapshot): ?UserFeedback
    {
        return $snapshot->feedbacks
            ->filter(fn (UserFeedback $feedback) =>
                $feedback->grade === null && ($feedback->user_id === null || $feedback->isAssignmentExpired())
            )
            ->first();
    }

    private function getAssignedNotGradedFeedback(SearchSnapshot $snapshot): ?UserFeedback
    {
        return $snapshot->feedbacks
            ->filter(fn (UserFeedback $feedback) =>
                $feedback->grade === null && $feedback->user_id !== null && !$feedback->isAssignmentExpired()
            )
            ->first();
    }
}
