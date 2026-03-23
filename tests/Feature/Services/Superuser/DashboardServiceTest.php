<?php

namespace Tests\Feature\Services\Superuser;

use App\Http\Middleware\UserOnline;
use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\Judge;
use App\Models\JudgeLog;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\Team;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Superuser\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;
    private User $user;
    private Team $team;
    private SearchEndpoint $endpoint;
    private SearchModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DashboardService();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = Team::factory()->create([
            Team::FIELD_USER_ID => $this->user->id,
            Team::FIELD_PERSONAL_TEAM => false,
        ]);
        $this->endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $this->user->id,
            SearchEndpoint::FIELD_TEAM_ID => $this->team->id,
        ]);
        $this->model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $this->user->id,
            SearchModel::FIELD_TEAM_ID => $this->team->id,
            SearchModel::FIELD_ENDPOINT_ID => $this->endpoint->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // getOverviewStats
    // -------------------------------------------------------------------------

    public function test_overview_stats_returns_correct_user_counts(): void
    {
        // setUp already created 1 user
        User::factory()->count(3)->create();

        $stats = $this->service->getOverviewStats();

        $this->assertEquals(4, $stats['users_total']);
    }

    public function test_overview_stats_counts_online_users(): void
    {
        // Online user (active within CACHE_MINUTES)
        User::factory()->create([
            User::FIELD_LAST_ACTIVE_AT => now()->subMinutes(UserOnline::CACHE_MINUTES - 1),
        ]);

        // Offline user (active beyond CACHE_MINUTES)
        User::factory()->create([
            User::FIELD_LAST_ACTIVE_AT => now()->subMinutes(UserOnline::CACHE_MINUTES + 10),
        ]);

        // User who was never active
        User::factory()->create([
            User::FIELD_LAST_ACTIVE_AT => null,
        ]);

        $stats = $this->service->getOverviewStats();

        $this->assertEquals(1, $stats['users_online']);
    }

    public function test_overview_stats_counts_teams(): void
    {
        // setUp creates 1 personal team + 1 non-personal team
        Team::factory()->create([Team::FIELD_PERSONAL_TEAM => false]);

        $stats = $this->service->getOverviewStats();

        // personal team from withPersonalTeam() + $this->team + new team
        $this->assertEquals(3, $stats['teams_total']);
    }

    public function test_overview_stats_counts_evaluations_by_status(): void
    {
        $this->createEvaluation(SearchEvaluation::STATUS_PENDING);
        $this->createEvaluation(SearchEvaluation::STATUS_PENDING);
        $this->createEvaluation(SearchEvaluation::STATUS_ACTIVE);
        $this->createEvaluation(SearchEvaluation::STATUS_FINISHED);
        $this->createEvaluation(SearchEvaluation::STATUS_FINISHED);
        $this->createEvaluation(SearchEvaluation::STATUS_FINISHED);

        $stats = $this->service->getOverviewStats();

        $this->assertEquals(6, $stats['evaluations_total']);
        $this->assertEquals(2, $stats['evaluations_pending']);
        $this->assertEquals(1, $stats['evaluations_active']);
        $this->assertEquals(3, $stats['evaluations_finished']);
    }

    public function test_overview_stats_counts_active_judges_and_providers(): void
    {
        Judge::factory()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'openai',
        ]);
        Judge::factory()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'anthropic',
        ]);
        // Archived judge should not be counted
        Judge::factory()->archived()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'google',
        ]);

        $stats = $this->service->getOverviewStats();

        $this->assertEquals(2, $stats['judges_active']);
        $this->assertEquals(2, $stats['judges_providers_count']);
    }

    public function test_overview_stats_counts_same_provider_once(): void
    {
        Judge::factory()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'openai',
        ]);
        Judge::factory()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'openai',
        ]);

        $stats = $this->service->getOverviewStats();

        $this->assertEquals(2, $stats['judges_active']);
        $this->assertEquals(1, $stats['judges_providers_count']);
    }

    public function test_overview_stats_returns_zeros_with_no_data(): void
    {
        $stats = $this->service->getOverviewStats();

        $this->assertEquals(0, $stats['evaluations_total']);
        $this->assertEquals(0, $stats['evaluations_active']);
        $this->assertEquals(0, $stats['evaluations_pending']);
        $this->assertEquals(0, $stats['evaluations_finished']);
        $this->assertEquals(0, $stats['judges_active']);
        $this->assertEquals(0, $stats['judges_providers_count']);
    }

    // -------------------------------------------------------------------------
    // getUserRegistrations
    // -------------------------------------------------------------------------

    public function test_user_registrations_returns_date_keyed_array(): void
    {
        $result = $this->service->getUserRegistrations(7);

        $this->assertCount(8, $result); // 7 days + today
        $this->assertArrayHasKey(now()->format('Y-m-d'), $result);
        $this->assertArrayHasKey(now()->subDays(7)->format('Y-m-d'), $result);
    }

    public function test_user_registrations_counts_users_per_day(): void
    {
        $today = now()->startOfDay();

        User::factory()->create([User::FIELD_CREATED_AT => $today->copy()->addHours(2)]);
        User::factory()->create([User::FIELD_CREATED_AT => $today->copy()->addHours(5)]);
        User::factory()->create([User::FIELD_CREATED_AT => now()->subDays(2)]);

        $result = $this->service->getUserRegistrations(7);

        // +1 because setUp user is also created today
        $this->assertEquals(3, $result[$today->format('Y-m-d')]);
        $this->assertEquals(1, $result[now()->subDays(2)->format('Y-m-d')]);
    }

    public function test_user_registrations_excludes_data_outside_period(): void
    {
        User::factory()->create([User::FIELD_CREATED_AT => now()->subDays(10)]);

        $result = $this->service->getUserRegistrations(7);

        // Only the setUp user counts (created today)
        $totalInPeriod = array_sum($result);
        $this->assertEquals(1, $totalInPeriod);
    }

    public function test_user_registrations_fills_gaps_with_zeros(): void
    {
        $result = $this->service->getUserRegistrations(7);

        foreach ($result as $date => $count) {
            $this->assertIsInt($count);
        }

        // Days with no registrations should be 0
        $yesterday = now()->subDays(1)->format('Y-m-d');
        // setUp user was created today, so yesterday should be 0
        $this->assertEquals(0, $result[$yesterday]);
    }

    // -------------------------------------------------------------------------
    // getFeedbacksGraded
    // -------------------------------------------------------------------------

    public function test_feedbacks_graded_returns_date_keyed_array(): void
    {
        $result = $this->service->getFeedbacksGraded(30);

        $this->assertCount(31, $result); // 30 days + today
    }

    public function test_feedbacks_graded_counts_only_graded_feedbacks(): void
    {
        $evaluation = $this->createEvaluation(SearchEvaluation::STATUS_ACTIVE);
        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);
        $snapshot = SearchSnapshot::withoutEvents(fn () => SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
        ]));

        // Graded feedback
        UserFeedback::factory()->graded()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_UPDATED_AT => now(),
        ]);

        // Ungraded feedback — should not count
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_UPDATED_AT => now(),
        ]);

        $result = $this->service->getFeedbacksGraded(7);
        $today = now()->format('Y-m-d');

        $this->assertEquals(1, $result[$today]);
    }

    // -------------------------------------------------------------------------
    // getEvaluationsByScale
    // -------------------------------------------------------------------------

    public function test_evaluations_by_scale_groups_correctly(): void
    {
        $this->createEvaluation(SearchEvaluation::STATUS_PENDING, 'binary');
        $this->createEvaluation(SearchEvaluation::STATUS_PENDING, 'binary');
        $this->createEvaluation(SearchEvaluation::STATUS_PENDING, 'graded');
        $this->createEvaluation(SearchEvaluation::STATUS_PENDING, 'detail');

        $result = $this->service->getEvaluationsByScale();

        $this->assertEquals(2, $result['binary']);
        $this->assertEquals(1, $result['graded']);
        $this->assertEquals(1, $result['detail']);
    }

    public function test_evaluations_by_scale_returns_empty_array_with_no_data(): void
    {
        $result = $this->service->getEvaluationsByScale();

        $this->assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // getMetricsDistribution
    // -------------------------------------------------------------------------

    public function test_metrics_distribution_returns_display_names_with_counts(): void
    {
        $evaluation = $this->createEvaluation(SearchEvaluation::STATUS_FINISHED);

        EvaluationMetric::withoutEvents(fn () => EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'ndcg',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]));
        EvaluationMetric::withoutEvents(fn () => EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'ndcg',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]));
        EvaluationMetric::withoutEvents(fn () => EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 5,
        ]));

        $result = $this->service->getMetricsDistribution();

        $this->assertNotEmpty($result);
        // Keys are display names like "nDCG@10", "P@5"
        $this->assertArrayHasKey('nDCG@10', $result);
        $this->assertEquals(2, $result['nDCG@10']);
        $this->assertArrayHasKey('P@5', $result);
        $this->assertEquals(1, $result['P@5']);
    }

    public function test_metrics_distribution_returns_empty_with_no_metrics(): void
    {
        $result = $this->service->getMetricsDistribution();

        $this->assertEmpty($result);
    }

    public function test_metrics_distribution_ordered_by_count_desc(): void
    {
        $evaluation = $this->createEvaluation(SearchEvaluation::STATUS_FINISHED);

        // Create 3 ndcg metrics and 1 precision
        for ($i = 0; $i < 3; $i++) {
            EvaluationMetric::withoutEvents(fn () => EvaluationMetric::factory()->create([
                EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
                EvaluationMetric::FIELD_SCORER_TYPE => 'ndcg',
                EvaluationMetric::FIELD_NUM_RESULTS => 10,
            ]));
        }
        EvaluationMetric::withoutEvents(fn () => EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]));

        $result = $this->service->getMetricsDistribution();

        $keys = array_keys($result);
        $this->assertEquals('nDCG@10', $keys[0]);
    }

    // -------------------------------------------------------------------------
    // getFeedbackStats
    // -------------------------------------------------------------------------

    public function test_feedback_stats_counts_graded_human_and_judge(): void
    {
        $evaluation = $this->createEvaluation(SearchEvaluation::STATUS_ACTIVE);
        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);
        $snapshot = SearchSnapshot::withoutEvents(fn () => SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
        ]));

        $judge = Judge::factory()->create([Judge::FIELD_TEAM_ID => $this->team->id]);

        // 2 human graded feedbacks
        UserFeedback::factory()->graded()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
        ]);
        UserFeedback::factory()->graded(2)->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
        ]);

        // 1 judge graded feedback
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => 3,
        ]);

        // 1 ungraded feedback
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
        ]);

        $result = $this->service->getFeedbackStats();

        $this->assertEquals(3, $result['graded']);
        $this->assertEquals(2, $result['human_count']);
        $this->assertEquals(1, $result['judge_count']);
    }

    public function test_feedback_stats_returns_zeros_with_no_data(): void
    {
        $result = $this->service->getFeedbackStats();

        $this->assertEquals(0, $result['graded']);
        $this->assertEquals(0, $result['human_count']);
        $this->assertEquals(0, $result['judge_count']);
    }

    // -------------------------------------------------------------------------
    // getJudgeSuccessRateByProvider
    // -------------------------------------------------------------------------

    public function test_judge_success_rate_groups_by_provider(): void
    {
        $judge = Judge::factory()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'openai',
        ]);

        $this->createJudgeLog($judge, 200);
        $this->createJudgeLog($judge, 200);
        $this->createJudgeLog($judge, 500);

        $result = $this->service->getJudgeSuccessRateByProvider();

        $this->assertArrayHasKey('openai', $result);
        $this->assertEquals(2, $result['openai']['success']);
        $this->assertEquals(1, $result['openai']['failed']);
    }

    public function test_judge_success_rate_null_status_code_counts_as_failed(): void
    {
        $judge = Judge::factory()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'anthropic',
        ]);

        $this->createJudgeLog($judge, null);

        $result = $this->service->getJudgeSuccessRateByProvider();

        $this->assertEquals(0, $result['anthropic']['success']);
        $this->assertEquals(1, $result['anthropic']['failed']);
    }

    public function test_judge_success_rate_multiple_providers(): void
    {
        $openaiJudge = Judge::factory()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'openai',
        ]);
        $anthropicJudge = Judge::factory()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'anthropic',
        ]);

        $this->createJudgeLog($openaiJudge, 200);
        $this->createJudgeLog($anthropicJudge, 200);
        $this->createJudgeLog($anthropicJudge, 429);

        $result = $this->service->getJudgeSuccessRateByProvider();

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result['openai']['success']);
        $this->assertEquals(0, $result['openai']['failed']);
        $this->assertEquals(1, $result['anthropic']['success']);
        $this->assertEquals(1, $result['anthropic']['failed']);
    }

    public function test_judge_success_rate_returns_empty_with_no_logs(): void
    {
        $result = $this->service->getJudgeSuccessRateByProvider();

        $this->assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // getAvgLatencyByDay
    // -------------------------------------------------------------------------

    public function test_avg_latency_by_day_returns_date_keyed_array(): void
    {
        $result = $this->service->getAvgLatencyByDay(7);

        $this->assertCount(8, $result); // 7 days + today
    }

    public function test_avg_latency_by_day_calculates_average(): void
    {
        $judge = Judge::factory()->create([Judge::FIELD_TEAM_ID => $this->team->id]);

        $this->createJudgeLog($judge, 200, ['latency_ms' => 100, 'created_at' => now()]);
        $this->createJudgeLog($judge, 200, ['latency_ms' => 300, 'created_at' => now()]);

        $result = $this->service->getAvgLatencyByDay(7);
        $today = now()->format('Y-m-d');

        $this->assertEquals(200, $result[$today]);
    }

    public function test_avg_latency_excludes_null_latency(): void
    {
        $judge = Judge::factory()->create([Judge::FIELD_TEAM_ID => $this->team->id]);

        $this->createJudgeLog($judge, 200, ['latency_ms' => 400, 'created_at' => now()]);
        $this->createJudgeLog($judge, 500, ['latency_ms' => null, 'created_at' => now()]);

        $result = $this->service->getAvgLatencyByDay(7);
        $today = now()->format('Y-m-d');

        $this->assertEquals(400, $result[$today]);
    }

    public function test_avg_latency_fills_gaps_with_zeros(): void
    {
        $result = $this->service->getAvgLatencyByDay(7);

        foreach ($result as $count) {
            $this->assertEquals(0, $count);
        }
    }

    public function test_avg_latency_excludes_data_outside_period(): void
    {
        $judge = Judge::factory()->create([Judge::FIELD_TEAM_ID => $this->team->id]);

        $this->createJudgeLog($judge, 200, [
            'latency_ms' => 999,
            'created_at' => now()->subDays(10),
        ]);

        $result = $this->service->getAvgLatencyByDay(7);

        $this->assertEquals(0, array_sum($result));
    }

    // -------------------------------------------------------------------------
    // getTokenUsageStats
    // -------------------------------------------------------------------------

    public function test_token_usage_stats_sums_tokens(): void
    {
        $judge = Judge::factory()->create([Judge::FIELD_TEAM_ID => $this->team->id]);

        $this->createJudgeLog($judge, 200, ['total_tokens' => 1000]);
        $this->createJudgeLog($judge, 200, ['total_tokens' => 3000]);

        $result = $this->service->getTokenUsageStats();

        $this->assertEquals(4000, $result['total_tokens']);
        $this->assertEquals(2000, $result['avg_per_request']);
    }

    public function test_token_usage_stats_finds_top_provider(): void
    {
        $openaiJudge = Judge::factory()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'openai',
        ]);
        $anthropicJudge = Judge::factory()->create([
            Judge::FIELD_TEAM_ID => $this->team->id,
            Judge::FIELD_PROVIDER => 'anthropic',
        ]);

        $this->createJudgeLog($openaiJudge, 200, ['total_tokens' => 5000]);
        $this->createJudgeLog($anthropicJudge, 200, ['total_tokens' => 1000]);

        $result = $this->service->getTokenUsageStats();

        $this->assertEquals('openai', $result['top_provider']);
    }

    public function test_token_usage_stats_returns_defaults_with_no_data(): void
    {
        $result = $this->service->getTokenUsageStats();

        $this->assertEquals(0, $result['total_tokens']);
        $this->assertEquals(0, $result['avg_per_request']);
        $this->assertEquals('—', $result['top_provider']);
    }

    // -------------------------------------------------------------------------
    // getTopTeams
    // -------------------------------------------------------------------------

    public function test_top_teams_excludes_personal_teams(): void
    {
        // setUp created a personal team — should not appear
        $result = $this->service->getTopTeams();

        foreach ($result as $team) {
            $this->assertFalse((bool) $team->personal_team);
        }
    }

    public function test_top_teams_ordered_by_evaluation_count_desc(): void
    {
        $team2 = Team::factory()->create([
            Team::FIELD_USER_ID => $this->user->id,
            Team::FIELD_PERSONAL_TEAM => false,
        ]);
        $endpoint2 = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $this->user->id,
            SearchEndpoint::FIELD_TEAM_ID => $team2->id,
        ]);
        $model2 = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $this->user->id,
            SearchModel::FIELD_TEAM_ID => $team2->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint2->id,
        ]);

        // team1 ($this->team) gets 1 evaluation
        $this->createEvaluation(SearchEvaluation::STATUS_FINISHED);

        // team2 gets 3 evaluations
        for ($i = 0; $i < 3; $i++) {
            SearchEvaluation::factory()->create([
                SearchEvaluation::FIELD_USER_ID => $this->user->id,
                SearchEvaluation::FIELD_MODEL_ID => $model2->id,
                SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_FINISHED,
            ]);
        }

        $result = $this->service->getTopTeams();

        $this->assertEquals($team2->id, $result->first()->id);
        $this->assertEquals(3, $result->first()->evaluations_count);
    }

    public function test_top_teams_includes_users_count(): void
    {
        $this->team->users()->attach($this->user->id, ['role' => 'admin']);
        $user2 = User::factory()->create();
        $this->team->users()->attach($user2->id, ['role' => 'admin']);

        $result = $this->service->getTopTeams();

        $teamResult = $result->firstWhere('id', $this->team->id);
        $this->assertNotNull($teamResult);
        $this->assertEquals(2, $teamResult->users_count);
    }

    public function test_top_teams_respects_limit(): void
    {
        // Create 3 non-personal teams (1 already exists from setUp)
        for ($i = 0; $i < 3; $i++) {
            Team::factory()->create([
                Team::FIELD_USER_ID => $this->user->id,
                Team::FIELD_PERSONAL_TEAM => false,
            ]);
        }

        $result = $this->service->getTopTeams(2);

        $this->assertCount(2, $result);
    }

    // -------------------------------------------------------------------------
    // getRecentEvaluations
    // -------------------------------------------------------------------------

    public function test_recent_evaluations_ordered_by_created_at_desc(): void
    {
        $old = $this->createEvaluation(SearchEvaluation::STATUS_FINISHED, 'binary', [
            SearchEvaluation::FIELD_CREATED_AT => now()->subDays(5),
        ]);
        $new = $this->createEvaluation(SearchEvaluation::STATUS_PENDING, 'graded', [
            SearchEvaluation::FIELD_CREATED_AT => now()->subDay(),
        ]);

        $result = $this->service->getRecentEvaluations();

        $this->assertEquals($new->id, $result->first()->id);
        $this->assertEquals($old->id, $result->last()->id);
    }

    public function test_recent_evaluations_loads_model_team_and_user_relations(): void
    {
        $this->createEvaluation(SearchEvaluation::STATUS_PENDING);

        $result = $this->service->getRecentEvaluations();

        $evaluation = $result->first();
        $this->assertTrue($evaluation->relationLoaded('model'));
        $this->assertTrue($evaluation->model->relationLoaded('team'));
        $this->assertTrue($evaluation->relationLoaded('user'));
    }

    public function test_recent_evaluations_respects_limit(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->createEvaluation(SearchEvaluation::STATUS_PENDING);
        }

        $result = $this->service->getRecentEvaluations(3);

        $this->assertCount(3, $result);
    }

    public function test_recent_evaluations_returns_empty_collection_with_no_data(): void
    {
        $result = $this->service->getRecentEvaluations();

        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createEvaluation(
        int $status,
        string $scaleType = 'binary',
        array $overrides = [],
    ): SearchEvaluation {
        return SearchEvaluation::factory()->create(array_merge([
            SearchEvaluation::FIELD_USER_ID => $this->user->id,
            SearchEvaluation::FIELD_MODEL_ID => $this->model->id,
            SearchEvaluation::FIELD_STATUS => $status,
            SearchEvaluation::FIELD_SCALE_TYPE => $scaleType,
        ], $overrides));
    }

    private function createJudgeLog(Judge $judge, ?int $statusCode, array $overrides = []): JudgeLog
    {
        return JudgeLog::create(array_merge([
            JudgeLog::FIELD_JUDGE_ID => $judge->id,
            JudgeLog::FIELD_TEAM_ID => $judge->team_id,
            JudgeLog::FIELD_PROVIDER => $judge->provider,
            JudgeLog::FIELD_MODEL => $judge->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => $statusCode,
            JudgeLog::FIELD_BATCH_SIZE => 1,
            JudgeLog::FIELD_SCALE_TYPE => 'binary',
        ], $overrides));
    }
}
