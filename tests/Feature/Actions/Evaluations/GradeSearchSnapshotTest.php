<?php

namespace Tests\Feature\Actions\Evaluations;

use App\Actions\Evaluations\GradeSearchSnapshot;
use App\Models\EvaluationKeyword;
use App\Models\Judge;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeSearchSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private GradeSearchSnapshot $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new GradeSearchSnapshot();
    }

    private function createSetup(): array
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

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
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

    public function test_grade_assigns_to_unassigned_feedback(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $snapshot->load('feedbacks');
        $this->action->grade($snapshot, $user, BinaryScale::RELEVANT);

        $feedback->refresh();
        $this->assertEquals($user->id, $feedback->user_id);
        $this->assertEquals(BinaryScale::RELEVANT, $feedback->grade);
    }

    public function test_grade_updates_own_graded_feedback(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        // User already has a graded feedback
        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);

        $snapshot->load('feedbacks');
        $this->action->grade($snapshot, $user, BinaryScale::RELEVANT);

        $feedback->refresh();
        $this->assertEquals(BinaryScale::RELEVANT, $feedback->grade);
    }

    public function test_grade_prefers_own_graded_over_unassigned(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        // User has an existing graded feedback
        $ownGraded = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);

        // There's also an unassigned feedback
        $unassigned = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $snapshot->load('feedbacks');
        $this->action->grade($snapshot, $user, BinaryScale::RELEVANT);

        $ownGraded->refresh();
        $unassigned->refresh();

        // Should update the own graded feedback
        $this->assertEquals(BinaryScale::RELEVANT, $ownGraded->grade);
        // Unassigned should be untouched
        $this->assertNull($unassigned->grade);
    }

    public function test_grade_uses_own_ungraded_when_no_graded_exists(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        // User has an ungraded but assigned feedback
        $ownUngraded = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        // Also an unassigned feedback
        $unassigned = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $snapshot->load('feedbacks');
        $this->action->grade($snapshot, $user, BinaryScale::RELEVANT);

        $ownUngraded->refresh();
        $unassigned->refresh();

        // Should use own ungraded
        $this->assertEquals(BinaryScale::RELEVANT, $ownUngraded->grade);
        $this->assertEquals($user->id, $ownUngraded->user_id);
        // Unassigned should remain untouched
        $this->assertNull($unassigned->grade);
    }

    public function test_grade_with_multiple_feedback_slots(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        // Two unassigned feedback slots (feedback strategy = 2)
        $fb1 = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $fb2 = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $snapshot->load('feedbacks');
        $this->action->grade($snapshot, $user, BinaryScale::RELEVANT);

        $fb1->refresh();
        $fb2->refresh();

        // Exactly one should be graded
        $gradedCount = collect([$fb1, $fb2])->whereNotNull('grade')->count();
        $this->assertEquals(1, $gradedCount);
    }

    public function test_grade_clears_judge_assignment_when_user_regrades_judge_feedback(): void
    {
        [$user, $evaluation, $keyword, $snapshot] = $this->createSetup();

        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);

        $snapshot->load('feedbacks');
        $this->action->grade($snapshot, $user, BinaryScale::RELEVANT);

        $feedback->refresh();
        $this->assertEquals($user->id, $feedback->user_id);
        $this->assertNull($feedback->judge_id);
        $this->assertEquals(BinaryScale::RELEVANT, $feedback->grade);
    }
}
