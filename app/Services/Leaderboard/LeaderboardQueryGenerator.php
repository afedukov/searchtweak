<?php

namespace App\Services\Leaderboard;

use App\Models\UserFeedback;
use Illuminate\Database\Eloquent\Builder;

class LeaderboardQueryGenerator
{
    public function getUserQuery(int $teamId, array $dates = []): Builder
    {
        return UserFeedback::team($teamId)
            ->graded()
            ->whereNotNull(UserFeedback::FIELD_USER_ID)
            ->selectRaw(UserFeedback::FIELD_USER_ID . ', count(*) as feedback_count')
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY count(*) DESC) as position')
            ->with('user')
            ->whereHas('user')
            ->whereBetween(UserFeedback::FIELD_UPDATED_AT, $dates)
            ->groupBy(UserFeedback::FIELD_USER_ID)
            ->orderByDesc('feedback_count')
            ->orderBy(UserFeedback::FIELD_USER_ID);
    }

    public function getJudgeQuery(int $teamId, array $dates = []): Builder
    {
        return UserFeedback::team($teamId)
            ->graded()
            ->whereNotNull(UserFeedback::FIELD_JUDGE_ID)
            ->selectRaw(UserFeedback::FIELD_JUDGE_ID . ', count(*) as feedback_count')
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY count(*) DESC) as position')
            ->with('judge.tags')
            ->whereHas('judge')
            ->whereBetween(UserFeedback::FIELD_UPDATED_AT, $dates)
            ->groupBy(UserFeedback::FIELD_JUDGE_ID)
            ->orderByDesc('feedback_count')
            ->orderBy(UserFeedback::FIELD_JUDGE_ID);
    }

    /**
     * @deprecated Use getUserQuery() instead
     */
    public function getQuery(int $teamId, array $dates = []): Builder
    {
        return $this->getUserQuery($teamId, $dates);
    }

    public function getUserDataset(Builder $query, int $limit = 10): array
    {
        return $query
            ->limit($limit)
            ->get()
            ->map(fn (UserFeedback $item) => [
                'label' => $item->user->name,
                'value' => $item->feedback_count,
            ])
            ->all();
    }

    public function getJudgeDataset(Builder $query, int $limit = 10): array
    {
        return $query
            ->limit($limit)
            ->get()
            ->map(fn (UserFeedback $item) => [
                'label' => ($item->judge?->name ?? 'Removed Judge') . ' (AI)',
                'value' => $item->feedback_count,
            ])
            ->all();
    }

    /**
     * @deprecated Use getUserDataset() instead
     */
    public function getDataset(Builder $query, int $limit = 10): array
    {
        return $this->getUserDataset($query, $limit);
    }
}
