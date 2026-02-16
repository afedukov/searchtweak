<?php

namespace Tests\Feature\Services\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Evaluations\UserFeedbackService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFeedbackServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserFeedbackService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserFeedbackService();
    }

    private function createEvaluationWithSnapshot(User $user): array
    {
        $team = $user->currentTeam;
        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
        ]);
        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $team->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);
        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $snapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-1',
            SearchSnapshot::FIELD_NAME => 'Test Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $snapshot->saveQuietly();

        return [$evaluation, $snapshot];
    }

    public function test_fetch_assigns_feedback_to_user(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user);

        // Create unassigned feedback
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $feedback = $this->service->fetch($user, $evaluation);

        $this->assertNotNull($feedback);
        $this->assertEquals($user->id, $feedback->user_id);
        $this->assertNull($feedback->grade);
    }

    public function test_fetch_returns_null_when_no_feedback_available(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user);

        $feedback = $this->service->fetch($user, $evaluation);

        $this->assertNull($feedback);
    }

    public function test_fetch_returns_already_assigned_feedback(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user);

        // Already assigned to this user
        $existing = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $feedback = $this->service->fetch($user, $evaluation);

        $this->assertNotNull($feedback);
        $this->assertEquals($existing->id, $feedback->id);
    }

    public function test_fetch_expired_assignment_is_reassigned(): void
    {
        $user1 = User::factory()->withPersonalTeam()->create();
        $user1->switchTeam($user1->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user1);

        $user2 = User::factory()->create();

        // Feedback assigned to user2 but expired
        $expired = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user2->id,
            UserFeedback::FIELD_GRADE => null,
        ]);
        // Set updated_at to 6 minutes ago (past the 5-minute lock)
        $expired->updated_at = Carbon::now()->subMinutes(6);
        $expired->saveQuietly();

        $feedback = $this->service->fetch($user1, $evaluation);

        $this->assertNotNull($feedback);
        $this->assertEquals($user1->id, $feedback->user_id);
    }

    public function test_previous_returns_last_graded_feedback(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user);

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => 1,
        ]);

        $previous = $this->service->previous($user, $evaluation);

        $this->assertNotNull($previous);
        $this->assertEquals($feedback->id, $previous->id);
    }

    public function test_previous_returns_null_when_none(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user);

        $previous = $this->service->previous($user, $evaluation);

        $this->assertNull($previous);
    }

    public function test_cache_tag_and_key_format(): void
    {
        $tag = UserFeedbackService::getUngradedSnapshotsCountCacheTag(42);
        $key = UserFeedbackService::getUngradedSnapshotsCountCacheKey(7);

        $this->assertEquals('ungraded-snapshots-count::team.42', $tag);
        $this->assertEquals('ungraded-snapshots-count::user.7', $key);
    }

    public function test_get_ungraded_snapshots_count_cached(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user);

        // Create ungraded feedback
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $count = $this->service->getUngradedSnapshotsCountCached($user);

        $this->assertEquals(1, $count);
    }

    public function test_get_ungraded_snapshots_count_excludes_graded(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user);

        // Already graded — should not be counted
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => 1,
        ]);

        $count = $this->service->getUngradedSnapshotsCountCached($user);

        $this->assertEquals(0, $count);
    }

    public function test_fetch_returns_null_when_all_graded(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user);

        // All feedback is graded — nothing to fetch
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => 1,
        ]);

        $feedback = $this->service->fetch($user, $evaluation);

        $this->assertNull($feedback);
    }

    public function test_fetch_does_not_steal_another_users_recent_assignment(): void
    {
        $user1 = User::factory()->withPersonalTeam()->create();
        $user1->switchTeam($user1->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user1);

        $user2 = User::factory()->create();

        // Feedback recently assigned to user2 (within the 5-minute lock)
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user2->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        // user1 tries to fetch — should get null since user2 still has the lock
        $feedback = $this->service->fetch($user1, $evaluation);

        $this->assertNull($feedback);
    }

    public function test_previous_ignores_ungraded_feedback(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);
        [$evaluation, $snapshot] = $this->createEvaluationWithSnapshot($user);

        // Ungraded feedback assigned to user
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $previous = $this->service->previous($user, $evaluation);

        // Should be null since the feedback is not graded
        $this->assertNull($previous);
    }
}
