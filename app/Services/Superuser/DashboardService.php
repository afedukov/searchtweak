<?php

namespace App\Services\Superuser;

use App\Http\Middleware\UserOnline;
use App\Models\EvaluationMetric;
use App\Models\Judge;
use App\Services\Scorers\ScorerFactory;
use App\Models\JudgeLog;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\Team;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const int CACHE_TTL_SECONDS = 300;

    /**
     * Get overview statistics for the platform dashboard.
     */
    public function getOverviewStats(): array
    {
        $onlineThreshold = now()->subMinutes(UserOnline::CACHE_MINUTES);

        $userStats = User::query()->selectRaw(
            'COUNT(*) as total, SUM(CASE WHEN ' . User::FIELD_LAST_ACTIVE_AT . ' > ? THEN 1 ELSE 0 END) as online',
            [$onlineThreshold],
        )->first();

        $evalStats = SearchEvaluation::query()->selectRaw(
            'COUNT(*) as total, '
            . 'SUM(CASE WHEN ' . SearchEvaluation::FIELD_STATUS . ' = ? THEN 1 ELSE 0 END) as pending, '
            . 'SUM(CASE WHEN ' . SearchEvaluation::FIELD_STATUS . ' = ? THEN 1 ELSE 0 END) as active, '
            . 'SUM(CASE WHEN ' . SearchEvaluation::FIELD_STATUS . ' = ? THEN 1 ELSE 0 END) as finished',
            [SearchEvaluation::STATUS_PENDING, SearchEvaluation::STATUS_ACTIVE, SearchEvaluation::STATUS_FINISHED],
        )->first();

        $judgeStats = Judge::active()->selectRaw(
            'COUNT(*) as total, COUNT(DISTINCT ' . Judge::FIELD_PROVIDER . ') as providers',
        )->first();

        $feedbackStats = UserFeedback::query()->selectRaw(
            'SUM(CASE WHEN ' . UserFeedback::FIELD_GRADE . ' IS NOT NULL THEN 1 ELSE 0 END) as graded, '
            . 'SUM(CASE WHEN ' . UserFeedback::FIELD_GRADE . ' IS NOT NULL AND ' . UserFeedback::FIELD_JUDGE_ID . ' IS NOT NULL THEN 1 ELSE 0 END) as judge_count',
        )->first();

        return [
            'users_total' => (int) $userStats->total,
            'users_online' => (int) $userStats->online,
            'teams_total' => Team::count(),
            'evaluations_total' => (int) $evalStats->total,
            'evaluations_active' => (int) $evalStats->active,
            'evaluations_pending' => (int) $evalStats->pending,
            'evaluations_finished' => (int) $evalStats->finished,
            'feedback_graded' => (int) $feedbackStats->graded,
            'feedback_judge_count' => (int) $feedbackStats->judge_count,
            'judges_active' => (int) $judgeStats->total,
            'judges_providers_count' => (int) $judgeStats->providers,
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
     * Get graded feedbacks grouped by date.
     */
    public function getFeedbacksGraded(int $days): array
    {
        return $this->getCountByDate(
            UserFeedback::query()->whereNotNull(UserFeedback::FIELD_GRADE),
            UserFeedback::FIELD_UPDATED_AT,
            $days,
        );
    }

    /**
     * Get evaluations count by scale type.
     */
    public function getEvaluationsByScale(int $days): array
    {
        return SearchEvaluation::query()
            ->where(SearchEvaluation::FIELD_CREATED_AT, '>=', now()->subDays($days)->startOfDay())
            ->select(SearchEvaluation::FIELD_SCALE_TYPE, DB::raw('COUNT(*) as count'))
            ->groupBy(SearchEvaluation::FIELD_SCALE_TYPE)
            ->pluck('count', SearchEvaluation::FIELD_SCALE_TYPE)
            ->toArray();
    }

    /**
     * Get metrics distribution (scorer_type + num_results label => count).
     */
    public function getMetricsDistribution(int $days): array
    {
        return EvaluationMetric::query()
            ->where(EvaluationMetric::FIELD_CREATED_AT, '>=', now()->subDays($days)->startOfDay())
            ->select(
                EvaluationMetric::FIELD_SCORER_TYPE,
                EvaluationMetric::FIELD_NUM_RESULTS,
                DB::raw('COUNT(*) as count'),
            )
            ->groupBy(EvaluationMetric::FIELD_SCORER_TYPE, EvaluationMetric::FIELD_NUM_RESULTS)
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(fn ($row) => [
                ScorerFactory::create($row->scorer_type)->getDisplayName($row->num_results) => (int) $row->count,
            ])
            ->toArray();
    }

    /**
     * Get feedback statistics.
     */
    public function getFeedbackStats(int $days): array
    {
        $stats = UserFeedback::query()
            ->where(UserFeedback::FIELD_UPDATED_AT, '>=', now()->subDays($days)->startOfDay())
            ->selectRaw(
                'SUM(CASE WHEN ' . UserFeedback::FIELD_GRADE . ' IS NOT NULL THEN 1 ELSE 0 END) as graded, '
                . 'SUM(CASE WHEN ' . UserFeedback::FIELD_GRADE . ' IS NOT NULL AND ' . UserFeedback::FIELD_USER_ID . ' IS NOT NULL THEN 1 ELSE 0 END) as human_count, '
                . 'SUM(CASE WHEN ' . UserFeedback::FIELD_GRADE . ' IS NOT NULL AND ' . UserFeedback::FIELD_JUDGE_ID . ' IS NOT NULL THEN 1 ELSE 0 END) as judge_count',
            )->first();

        return [
            'graded' => (int) $stats->graded,
            'human_count' => (int) $stats->human_count,
            'judge_count' => (int) $stats->judge_count,
        ];
    }

    /**
     * Get judge success rate grouped by provider.
     */
    public function getJudgeSuccessRateByProvider(int $days): array
    {
        return Cache::remember("dashboard:judge_success_rate:{$days}", self::CACHE_TTL_SECONDS, function () use ($days) {
            $results = JudgeLog::query()
                ->where(JudgeLog::FIELD_CREATED_AT, '>=', now()->subDays($days)->startOfDay())
                ->select(
                    JudgeLog::FIELD_PROVIDER,
                    DB::raw('SUM(CASE WHEN ' . JudgeLog::FIELD_HTTP_STATUS_CODE . ' BETWEEN 200 AND 299 THEN 1 ELSE 0 END) as success_count'),
                    DB::raw('SUM(CASE WHEN ' . JudgeLog::FIELD_HTTP_STATUS_CODE . ' < 200 OR ' . JudgeLog::FIELD_HTTP_STATUS_CODE . ' >= 300 OR ' . JudgeLog::FIELD_HTTP_STATUS_CODE . ' IS NULL THEN 1 ELSE 0 END) as failed_count'),
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
        });
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
    public function getTokenUsageStats(int $days): array
    {
        return Cache::remember("dashboard:token_usage:{$days}", self::CACHE_TTL_SECONDS, function () use ($days) {
            $startDate = now()->subDays($days)->startOfDay();

            $stats = JudgeLog::query()
                ->where(JudgeLog::FIELD_CREATED_AT, '>=', $startDate)
                ->select(
                    DB::raw('COALESCE(SUM(' . JudgeLog::FIELD_TOTAL_TOKENS . '), 0) as total_tokens'),
                    DB::raw('COALESCE(ROUND(AVG(' . JudgeLog::FIELD_TOTAL_TOKENS . ')), 0) as avg_per_request'),
                )
                ->first();

            $topProvider = JudgeLog::query()
                ->where(JudgeLog::FIELD_CREATED_AT, '>=', $startDate)
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
        });
    }

    /**
     * Get most active teams by evaluation count.
     */
    public function getTopTeams(int $limit = 5): Collection
    {
        return Team::query()
            ->where(Team::FIELD_PERSONAL_TEAM, false)
            ->withCount('users')
            ->addSelect([
                'evaluations_count' => SearchEvaluation::query()
                    ->selectRaw('COUNT(*)')
                    ->join(
                        'search_models',
                        'search_evaluations.' . SearchEvaluation::FIELD_MODEL_ID,
                        '=',
                        'search_models.' . SearchModel::FIELD_ID,
                    )
                    ->whereColumn('search_models.' . SearchModel::FIELD_TEAM_ID, 'teams.' . Team::FIELD_ID),
            ])
            ->orderByDesc('evaluations_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent evaluations with related data.
     */
    public function getRecentEvaluations(int $limit = 5): Collection
    {
        return SearchEvaluation::query()
            ->with(['model.team', 'user', 'model.endpoint'])
            ->orderByDesc(SearchEvaluation::FIELD_CREATED_AT)
            ->limit($limit)
            ->get();
    }

    /**
     * Get record counts grouped by date for a given model query.
     */
    private function getCountByDate(Builder $query, string $dateField, int $days): array
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
