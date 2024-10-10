<?php

namespace App\Services\Leaderboard;

use App\Models\UserFeedback;
use Illuminate\Database\Eloquent\Builder;

class LeaderboardQueryGenerator
{
    public function getQuery(int $teamId, array $dates = []): Builder
    {
        return UserFeedback::team($teamId)
            ->graded()
            ->selectRaw(UserFeedback::FIELD_USER_ID . ', count(*) as feedback_count')
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY count(*) DESC) as position')
            ->with('user')
            ->whereHas('user')
            ->whereBetween(UserFeedback::FIELD_UPDATED_AT, $dates)
            ->groupBy(UserFeedback::FIELD_USER_ID)
            ->orderByDesc('feedback_count')
            ->orderBy(UserFeedback::FIELD_USER_ID);
    }

    public function getDataset(Builder $query, int $limit = 10): array
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
}
