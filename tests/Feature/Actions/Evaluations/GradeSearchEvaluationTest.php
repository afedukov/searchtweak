<?php

namespace Tests\Feature\Actions\Evaluations;

use App\Actions\Evaluations\GradeSearchEvaluation;
use App\Models\EvaluationKeyword;
use App\Models\Judge;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Evaluations\UserFeedbackService;
use App\Services\Scorers\Scales\BinaryScale;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeSearchEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private GradeSearchEvaluation $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new GradeSearchEvaluation();
    }

    private function createSetup(string $status = null): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);

        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $user->currentTeam->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);

        $evalState = $status ?? SearchEvaluation::STATUS_ACTIVE;

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_STATUS => $evalState,
            SearchEvaluation::FIELD_MAX_NUM_RESULTS => 10,
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

        return [$user, $evaluation, $keyword, $snapshot];
    }

    public function test_grade_assigns_grade_to_feedback(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $this->action->grade($feedback, $user, BinaryScale::RELEVANT);

        $feedback->refresh();
        $this->assertEquals(BinaryScale::RELEVANT, $feedback->grade);
    }

    public function test_grade_throws_when_evaluation_is_not_active(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup(SearchEvaluation::STATUS_PENDING);

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Evaluation is not active');

        $this->action->grade($feedback, $user, BinaryScale::RELEVANT);
    }

    public function test_grade_throws_when_assigned_to_another_user(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        $anotherUser = User::factory()->withPersonalTeam()->create();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $anotherUser->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Snapshot assigned to another user');

        $this->action->grade($feedback, $user, BinaryScale::RELEVANT);
    }

    public function test_grade_throws_when_assignment_expired(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        // Simulate expired assignment by changing updated_at to the past
        $feedback->updated_at = Carbon::now()->subMinutes(UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES + 1);
        $feedback->saveQuietly();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Snapshot assignment expired');

        $this->action->grade($feedback, $user, BinaryScale::RELEVANT);
    }

    public function test_grade_allows_regrading_already_graded_feedback(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);

        $this->action->grade($feedback, $user, BinaryScale::RELEVANT);

        $feedback->refresh();
        $this->assertEquals(BinaryScale::RELEVANT, $feedback->grade);
    }

    public function test_grade_clears_judge_assignment_when_user_grades(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);

        $this->action->grade($feedback, $user, BinaryScale::RELEVANT);

        $feedback->refresh();
        $this->assertEquals(BinaryScale::RELEVANT, $feedback->grade);
        $this->assertNull($feedback->judge_id);
    }
}
