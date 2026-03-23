<?php

namespace App\Services\Superuser;

use App\Http\Middleware\UserOnline;
use App\Models\Judge;
use App\Models\JudgeLog;
use App\Models\SearchEvaluation;
use App\Models\Team;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get overview statistics for the platform dashboard.
     */
    public function getOverviewStats(): array
    {
        $onlineThreshold = now()->subMinutes(UserOnline::CACHE_MINUTES);

        return [
            'users_total' => User::count(),
            'users_online' => User::where(User::FIELD_LAST_ACTIVE_AT, '>', $onlineThreshold)->count(),
            'teams_total' => Team::count(),
            'teams_personal' => Team::where(Team::FIELD_PERSONAL_TEAM, true)->count(),
            'teams_shared' => Team::where(Team::FIELD_PERSONAL_TEAM, false)->count(),
            'evaluations_total' => SearchEvaluation::count(),
            'evaluations_active' => SearchEvaluation::active()->count(),
            'evaluations_pending' => SearchEvaluation::pending()->count(),
            'evaluations_finished' => SearchEvaluation::finished()->count(),
            'judges_active' => Judge::active()->count(),
            'judges_providers_count' => Judge::active()
                ->distinct(Judge::FIELD_PROVIDER)
                ->count(Judge::FIELD_PROVIDER),
        ];
    }

    /**
     * Get user registrations grouped by date.
     */
    public function getUserRegistrations(int $days): array
    {
        return $this->getCountByDate(User::query(), User::FIELD_CREATED_AT, $days);
    }

    /**
     * Get evaluations created grouped by date.
     */
    public function getEvaluationsCreated(int $days): array
    {
        return $this->getCountByDate(SearchEvaluation::query(), SearchEvaluation::FIELD_CREATED_AT, $days);
    }

    /**
     * Get evaluations count by status.
     */
    public function getEvaluationsByStatus(): array
    {
        return [
            'Pending' => SearchEvaluation::pending()->count(),
            'Active' => SearchEvaluation::active()->count(),
            'Finished' => SearchEvaluation::finished()->count(),
        ];
    }

    /**
     * Get evaluations count by scale type.
     */
    public function getEvaluationsByScale(): array
    {
        return SearchEvaluation::query()
            ->select(SearchEvaluation::FIELD_SCALE_TYPE, DB::raw('COUNT(*) as count'))
            ->groupBy(SearchEvaluation::FIELD_SCALE_TYPE)
            ->pluck('count', SearchEvaluation::FIELD_SCALE_TYPE)
            ->toArray();
    }

    /**
     * Get feedback statistics.
     */
    public function getFeedbackStats(): array
    {
        $total = UserFeedback::count();
        $graded = UserFeedback::whereNotNull(UserFeedback::FIELD_GRADE)->count();
        $humanCount = UserFeedback::whereNotNull(UserFeedback::FIELD_USER_ID)
            ->whereNotNull(UserFeedback::FIELD_GRADE)
            ->count();
        $judgeCount = UserFeedback::whereNotNull(UserFeedback::FIELD_JUDGE_ID)
            ->whereNotNull(UserFeedback::FIELD_GRADE)
            ->count();

        return [
            'total' => $total,
            'graded_pct' => $total > 0 ? round(($graded / $total) * 100, 1) : 0,
            'human_count' => $humanCount,
            'judge_count' => $judgeCount,
        ];
    }

    /**
     * Get judge success rate grouped by provider.
     */
    public function getJudgeSuccessRateByProvider(): array
    {
        $results = JudgeLog::query()
            ->select(
                JudgeLog::FIELD_PROVIDER,
                DB::raw('SUM(CASE WHEN http_status_code BETWEEN 200 AND 299 THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN http_status_code < 200 OR http_status_code >= 300 OR http_status_code IS NULL THEN 1 ELSE 0 END) as failed_count'),
            )
            ->groupBy(JudgeLog::FIELD_PROVIDER)
            ->get();

        $data = [];
        foreach ($results as $row) {
            $data[$row->provider] = [
                'success' => (int) $row->success_count,
                'failed' => (int) $row->failed_count,
            ];
        }

        return $data;
    }

    /**
     * Get average latency by day for judge logs.
     */
    public function getAvgLatencyByDay(int $days): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        $results = JudgeLog::query()
            ->select(
                DB::raw('DATE(' . JudgeLog::FIELD_CREATED_AT . ') as date'),
                DB::raw('ROUND(AVG(' . JudgeLog::FIELD_LATENCY_MS . ')) as avg_ms'),
            )
            ->where(JudgeLog::FIELD_CREATED_AT, '>=', $startDate)
            ->whereNotNull(JudgeLog::FIELD_LATENCY_MS)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('avg_ms', 'date');

        return $this->fillDateGaps($results->toArray(), $days);
    }

    /**
     * Get token usage statistics.
     */
    public function getTokenUsageStats(): array
    {
        $stats = JudgeLog::query()
            ->select(
                DB::raw('COALESCE(SUM(' . JudgeLog::FIELD_TOTAL_TOKENS . '), 0) as total_tokens'),
                DB::raw('COALESCE(ROUND(AVG(' . JudgeLog::FIELD_TOTAL_TOKENS . ')), 0) as avg_per_request'),
            )
            ->first();

        $topProvider = JudgeLog::query()
            ->select(JudgeLog::FIELD_PROVIDER, DB::raw('SUM(' . JudgeLog::FIELD_TOTAL_TOKENS . ') as total'))
            ->whereNotNull(JudgeLog::FIELD_TOTAL_TOKENS)
            ->groupBy(JudgeLog::FIELD_PROVIDER)
            ->orderByDesc('total')
            ->first();

        return [
            'total_tokens' => (int) $stats->total_tokens,
            'avg_per_request' => (int) $stats->avg_per_request,
            'top_provider' => $topProvider?->provider ?? '—',
        ];
    }

    /**
     * Get most active teams by evaluation count.
     */
    public function getTopTeams(int $limit = 5): Collection
    {
        return Team::query()
            ->where(Team::FIELD_PERSONAL_TEAM, false)
            ->withCount(['searchEvaluations', 'users'])
            ->orderByDesc('search_evaluations_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent evaluations with related data.
     */
    public function getRecentEvaluations(int $limit = 5): Collection
    {
        return SearchEvaluation::query()
            ->with(['team', 'user'])
            ->orderByDesc(SearchEvaluation::FIELD_CREATED_AT)
            ->limit($limit)
            ->get();
    }

    /**
     * Get record counts grouped by date for a given model query.
     */
    private function getCountByDate($query, string $dateField, int $days): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        $results = $query
            ->select(DB::raw("DATE($dateField) as date"), DB::raw('COUNT(*) as count'))
            ->where($dateField, '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return $this->fillDateGaps($results->toArray(), $days);
    }

    /**
     * Fill missing dates with zero values.
     */
    private function fillDateGaps(array $data, int $days): array
    {
        $filled = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $filled[$date] = $data[$date] ?? 0;
        }

        return $filled;
    }
}
