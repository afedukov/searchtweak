<?php

namespace App\Services\Evaluations;

use App\Models\SearchEvaluation;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class UserFeedbackService
{
    public const int FEEDBACK_LOCK_TIMEOUT_MINUTES = 5;

    /**
     * Fetch feedback for the user from the evaluation pool and assign it to the user.
     *
     * Feedback is assigned to the user for a limited time of FEEDBACK_LOCK_TIMEOUT_MINUTES,
     * after which it is treated as unassigned and can be fetched by another user.
     *
     * If evaluation is provided, feedback is fetched from the evaluation pool. For admins only.
     * Evaluation tags are not considered.
     *
     * @param User $user
     * @param SearchEvaluation|null $evaluation
     *
     * @return UserFeedback|null
     */
    public function fetch(User $user, ?SearchEvaluation $evaluation): ?UserFeedback
    {
        if ($evaluation === null) {
            $pool = UserFeedback::globalPool($user);
        } else {
            $pool = UserFeedback::evaluationPool($evaluation->id);
        }

        $assignedFeedback = $pool->clone()
            ->with('snapshot.keyword.evaluation.tags')
            ->assignedTo($user->id)
            ->ungraded()
            ->get()
            ->filter(fn (UserFeedback $feedback) => $evaluation !== null || $feedback->isAvailableTo($user))
            ->first();

        if ($assignedFeedback) {
            return $assignedFeedback;
        }

        $allFeedback = $pool->clone()
            ->with('snapshot.keyword.evaluation.tags')
            ->get()
            ->filter(fn (UserFeedback $feedback) => $evaluation !== null || $feedback->isAvailableTo($user))
            ->each(function (UserFeedback $feedback) {
                // Reset feedback assignment if it is not graded and the lock timeout has passed
                if ($feedback->grade === null && $feedback->user_id && $feedback->isAssignmentExpired()) {
                    $feedback->user_id = null;
                }
            })
            ->groupBy(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID);

        $userPool = collect();

        /** @var Collection<UserFeedback> $snapshotFeedbacks */
        foreach ($allFeedback as $snapshotFeedbacks) {
            $hasSnapshotFeedback = $snapshotFeedbacks->contains(UserFeedback::FIELD_USER_ID, $user->id);

            if (!$hasSnapshotFeedback) {
                foreach ($snapshotFeedbacks as $snapshotFeedback) {
                    if ($snapshotFeedback->user_id === null && $snapshotFeedback->grade === null) {
                        $userPool->push($snapshotFeedback);
                    }
                }
            }
        }

        if ($userPool->isEmpty()) {
            return null;
        }

        /** @var UserFeedback $feedback */
        $feedback = $userPool->random();

        $feedback->user_id = $user->id;
        $feedback->updateTimestamps();
        $feedback->save();

        return $feedback;
    }

    private function getGlobalPoolSnapshotsCount(User $user): int
    {
        $count = 0;

        $alreadyGradedSnapshots = UserFeedback::globalPool($user)
            ->graded()
            ->where(UserFeedback::FIELD_USER_ID, $user->id)
            ->pluck(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, UserFeedback::FIELD_SEARCH_SNAPSHOT_ID)
            ->all();

        $groupedFeedbacks = UserFeedback::globalPool($user)
            ->ungraded()
            ->with('snapshot.keyword.evaluation.tags')
            ->get()
            ->filter(fn (UserFeedback $feedback) => $feedback->isAvailableTo($user))
            ->groupBy(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID);

        /** @var Collection<UserFeedback> $feedbacks */
        foreach ($groupedFeedbacks as $snapshotId => $feedbacks) {
            if (isset($alreadyGradedSnapshots[$snapshotId])) {
                continue;
            }

            $hasFeedbackToGrade = $feedbacks
                ->filter(fn (UserFeedback $feedback) =>
                    $feedback->isUngradedAssignedTo($user->id) || $feedback->isUngradedUnassigned()
                )
                ->count() > 0;

            if ($hasFeedbackToGrade) {
                $count++;
            }
        }

        return $count;
    }

    public function getUngradedSnapshotsCountCached(User $user): int
    {
        $tag = $this->getUngradedSnapshotsCountCacheTag($user->current_team_id);
        $key = $this->getUngradedSnapshotsCountCacheKey($user->id);

        return Cache::tags($tag)->remember($key, 3600, fn () => $this->getGlobalPoolSnapshotsCount($user));
    }

    public static function getUngradedSnapshotsCountCacheTag(int $teamId): string
    {
        return sprintf('ungraded-snapshots-count::team.%d', $teamId);
    }

    public static function getUngradedSnapshotsCountCacheKey(int $userId): string
    {
        return sprintf('ungraded-snapshots-count::user.%d', $userId);
    }

    public static function flushUngradedSnapshotsCountCache(int $teamId): void
    {
        Cache::tags(UserFeedbackService::getUngradedSnapshotsCountCacheTag($teamId))->flush();
    }
}
