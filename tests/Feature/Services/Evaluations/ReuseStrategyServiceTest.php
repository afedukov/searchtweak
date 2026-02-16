<?php

namespace Tests\Feature\Services\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Evaluations\ReuseStrategyService;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReuseStrategyServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReuseStrategyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReuseStrategyService();
    }

    private function createSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
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

        return [$user, $team, $model];
    }

    public function test_reuse_query_doc_strategy(): void
    {
        [$user, $team, $model] = $this->createSetup();

        // Create a finished evaluation with graded feedback
        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);

        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'laptop',
        ]);

        $oldSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-123',
            SearchSnapshot::FIELD_NAME => 'Old Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $oldSnapshot->saveQuietly();

        $grader = User::factory()->create();
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        // Create new evaluation with same keyword and doc_id
        $newEval = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'laptop',
        ]);

        $newSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-123',
            SearchSnapshot::FIELD_NAME => 'New Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $newSnapshot->saveQuietly();

        // Create ungraded feedback
        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $newSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        // Add a metric for recalculation
        EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]);

        $this->service->apply($newEval);

        $feedback->refresh();
        $this->assertEquals($grader->id, $feedback->user_id);
        $this->assertEquals(BinaryScale::RELEVANT, $feedback->grade);
    }

    public function test_reuse_skips_archived_evaluations(): void
    {
        [$user, $team, $model] = $this->createSetup();

        // Create an ARCHIVED finished evaluation
        $archivedEval = SearchEvaluation::factory()->finished()->archived()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);

        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $archivedEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'phone',
        ]);

        $oldSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-456',
            SearchSnapshot::FIELD_NAME => 'Archived Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $oldSnapshot->saveQuietly();

        $grader = User::factory()->create();
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        // Create new evaluation
        $newEval = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'phone',
        ]);

        $newSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-456',
            SearchSnapshot::FIELD_NAME => 'New Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $newSnapshot->saveQuietly();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $newSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $this->service->apply($newEval);

        $feedback->refresh();
        // Should NOT reuse from archived evaluation
        $this->assertNull($feedback->user_id);
        $this->assertNull($feedback->grade);
    }

    public function test_throws_on_invalid_strategy(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_NONE,
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->apply($evaluation);
    }

    public function test_reuse_requires_matching_scale_type(): void
    {
        [$user, $team, $model] = $this->createSetup();

        // Create a graded (not binary) finished evaluation
        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => 'graded',
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);

        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'tablet',
        ]);

        $oldSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-789',
            SearchSnapshot::FIELD_NAME => 'Graded Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $oldSnapshot->saveQuietly();

        $grader = User::factory()->create();
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader->id,
            UserFeedback::FIELD_GRADE => 2,
        ]);

        // Create new BINARY evaluation (different scale)
        $newEval = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'tablet',
        ]);

        $newSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-789',
            SearchSnapshot::FIELD_NAME => 'New Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $newSnapshot->saveQuietly();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $newSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $this->service->apply($newEval);

        $feedback->refresh();
        // Should NOT reuse because scale types differ
        $this->assertNull($feedback->grade);
    }

    public function test_reuse_query_doc_position_strategy(): void
    {
        [$user, $team, $model] = $this->createSetup();

        // Create finished evaluation
        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);

        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'shoes',
        ]);

        $oldSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_POSITION => 3,
            SearchSnapshot::FIELD_DOC_ID => 'doc-shoe',
            SearchSnapshot::FIELD_NAME => 'Shoe Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $oldSnapshot->saveQuietly();

        $grader = User::factory()->create();
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        // New evaluation with QUERY_DOC_POSITION
        $newEval = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC_POSITION,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'shoes',
        ]);

        // Same doc, SAME position
        $newSnapshotMatch = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_POSITION => 3,
            SearchSnapshot::FIELD_DOC_ID => 'doc-shoe',
            SearchSnapshot::FIELD_NAME => 'New Shoe Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $newSnapshotMatch->saveQuietly();

        $feedbackMatch = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $newSnapshotMatch->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        // Same doc, DIFFERENT position — should NOT get reused
        $newSnapshotNoMatch = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_POSITION => 5,
            SearchSnapshot::FIELD_DOC_ID => 'doc-shoe',
            SearchSnapshot::FIELD_NAME => 'New Shoe Doc Different Pos',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $newSnapshotNoMatch->saveQuietly();

        $feedbackNoMatch = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $newSnapshotNoMatch->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]);

        $this->service->apply($newEval);

        $feedbackMatch->refresh();
        $this->assertEquals($grader->id, $feedbackMatch->user_id);
        $this->assertEquals(BinaryScale::RELEVANT, $feedbackMatch->grade);

        $feedbackNoMatch->refresh();
        // Position doesn't match, so should NOT be reused
        $this->assertNull($feedbackNoMatch->user_id);
        $this->assertNull($feedbackNoMatch->grade);
    }

    public function test_reuse_does_not_assign_already_graded_user(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $grader = User::factory()->create();

        // Create finished evaluation with graded feedback from $grader
        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);

        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'monitor',
        ]);

        $oldSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-mon',
            SearchSnapshot::FIELD_NAME => 'Monitor Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $oldSnapshot->saveQuietly();

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        // New evaluation — $grader already has a graded feedback on this snapshot
        $newEval = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 2,
            ],
        ]);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'monitor',
        ]);

        $newSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-mon',
            SearchSnapshot::FIELD_NAME => 'New Monitor Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $newSnapshot->saveQuietly();

        // Grader already has a graded feedback on this snapshot
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $newSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);

        // Another empty slot
        $feedbackEmpty = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $newSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $this->service->apply($newEval);

        $feedbackEmpty->refresh();
        // $grader already has feedback on this snapshot, so the empty slot should NOT get $grader again
        $this->assertNotEquals($grader->id, $feedbackEmpty->user_id);
    }
}
