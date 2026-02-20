<?php

namespace Tests\Feature\Services\Evaluations;

use App\Jobs\Evaluations\ProcessJudgeEvaluationJob;
use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\Judge;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Evaluations\ReuseStrategyService;
use App\Services\Judges\AbstractJudgeHandler;
use App\Services\Judges\JudgeHandlerFactory;
use App\Services\Scorers\Scales\BinaryScale;
use GuzzleHttp\ClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
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

    public function test_reuse_query_doc_strategy_includes_judge_feedback(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);

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
            EvaluationKeyword::FIELD_KEYWORD => 'camera',
        ]);

        $oldSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-cam',
            SearchSnapshot::FIELD_NAME => 'Camera Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $oldSnapshot->saveQuietly();

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            UserFeedback::FIELD_REASON => 'AI reused reason',
        ]);

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
            EvaluationKeyword::FIELD_KEYWORD => 'camera',
        ]);

        $newSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-cam',
            SearchSnapshot::FIELD_NAME => 'New Camera Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $newSnapshot->saveQuietly();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $newSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => null,
            UserFeedback::FIELD_GRADE => null,
            UserFeedback::FIELD_REASON => null,
        ]);

        $this->service->apply($newEval);

        $feedback->refresh();
        $this->assertNull($feedback->user_id);
        $this->assertEquals($judge->id, $feedback->judge_id);
        $this->assertEquals(BinaryScale::RELEVANT, $feedback->grade);
        $this->assertEquals('AI reused reason', $feedback->reason);
    }

    public function test_reuse_does_not_assign_same_judge_twice_on_snapshot(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $judge1 = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);
        $judge2 = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);

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
            EvaluationKeyword::FIELD_KEYWORD => 'mouse',
        ]);

        $oldSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-mouse',
            SearchSnapshot::FIELD_NAME => 'Mouse Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $oldSnapshot->saveQuietly();

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judge1->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            UserFeedback::FIELD_REASON => 'Judge 1 grade',
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judge2->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
            UserFeedback::FIELD_REASON => 'Judge 2 grade',
        ]);

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
            EvaluationKeyword::FIELD_KEYWORD => 'mouse',
        ]);

        $newSnapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-mouse',
            SearchSnapshot::FIELD_NAME => 'New Mouse Doc',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $newSnapshot->saveQuietly();

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $newSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judge1->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);

        $emptyFeedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $newSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => null,
            UserFeedback::FIELD_GRADE => null,
            UserFeedback::FIELD_REASON => null,
        ]);

        $this->service->apply($newEval);

        $emptyFeedback->refresh();
        $this->assertNull($emptyFeedback->user_id);
        $this->assertEquals($judge2->id, $emptyFeedback->judge_id);
        $this->assertEquals(BinaryScale::IRRELEVANT, $emptyFeedback->grade);
        $this->assertEquals('Judge 2 grade', $emptyFeedback->reason);
    }

    public function test_reuse_mixed_human_and_judge_pool_assigns_all_unique_slots(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $grader1 = User::factory()->create();
        $grader2 = User::factory()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);

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
            EvaluationKeyword::FIELD_KEYWORD => 'speaker',
        ]);

        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-speaker',
        ]);

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader1->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader2->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            UserFeedback::FIELD_REASON => 'AI reusable',
        ]);

        $newEval = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 3,
            ],
        ]);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'speaker',
        ]);

        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-speaker',
        ]);

        $this->service->apply($newEval);

        $feedbacks = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->orderBy(UserFeedback::FIELD_ID)
            ->get();

        $this->assertCount(3, $feedbacks);
        $this->assertSame(3, $feedbacks->whereNotNull(UserFeedback::FIELD_GRADE)->count());
        $this->assertEqualsCanonicalizing(
            [$grader1->id, $grader2->id],
            $feedbacks->whereNotNull(UserFeedback::FIELD_USER_ID)->pluck(UserFeedback::FIELD_USER_ID)->all()
        );
        $this->assertSame([$judge->id], $feedbacks->whereNotNull(UserFeedback::FIELD_JUDGE_ID)->pluck(UserFeedback::FIELD_JUDGE_ID)->all());
    }

    public function test_reuse_includes_judge_only_if_judge_matches_evaluation_tags(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $tagAllowed = \App\Models\Tag::factory()->create(['team_id' => $team->id]);
        $tagOther = \App\Models\Tag::factory()->create(['team_id' => $team->id]);

        $judgeAllowed = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);
        $judgeAllowed->tags()->attach($tagAllowed->id);

        $judgeBlocked = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);
        $judgeBlocked->tags()->attach($tagOther->id);

        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'keyboard',
        ]);
        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-kbd',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judgeAllowed->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            UserFeedback::FIELD_REASON => 'Allowed judge',
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judgeBlocked->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
            UserFeedback::FIELD_REASON => 'Blocked judge',
        ]);

        $newEval = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
        $newEval->tags()->attach($tagAllowed->id);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'keyboard',
        ]);
        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-kbd',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $this->service->apply($newEval);

        $feedback = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->first();

        $this->assertNotNull($feedback);
        $this->assertSame($judgeAllowed->id, $feedback->judge_id);
        $this->assertNotSame($judgeBlocked->id, $feedback->judge_id);
    }

    public function test_reuse_with_strategy_three_then_judge_job_completes_remaining_slot(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $grader = User::factory()->create();
        $oldJudge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);
        $activeJudge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);

        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'printer',
        ]);
        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-prn',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $oldJudge->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
            UserFeedback::FIELD_REASON => 'Old AI grade',
        ]);

        $newEval = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 3,
            ],
        ]);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'printer',
        ]);
        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-prn',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $this->service->apply($newEval);

        $handler = new class(Mockery::mock(ClientInterface::class)) extends AbstractJudgeHandler {
            public function grade(Judge $judge, string $prompt, array $validGrades): array
            {
                return [[
                    'pair_index' => 0,
                    'grade' => BinaryScale::RELEVANT,
                    'reason' => 'Filled by active AI',
                ]];
            }
        };

        $factory = Mockery::mock(JudgeHandlerFactory::class);
        $factory->shouldReceive('create')->once()->withArgs(function (Judge $judge) use ($activeJudge) {
            return $judge->id === $activeJudge->id;
        })->andReturn($handler);

        (new ProcessJudgeEvaluationJob($newEval->id))->handle($factory);

        $feedbacks = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->orderBy(UserFeedback::FIELD_ID)
            ->get();

        $this->assertCount(3, $feedbacks);
        $this->assertSame(3, $feedbacks->whereNotNull(UserFeedback::FIELD_GRADE)->count());
        $this->assertSame(1, $feedbacks->where(UserFeedback::FIELD_USER_ID, $grader->id)->count());
        $this->assertSame(1, $feedbacks->where(UserFeedback::FIELD_JUDGE_ID, $oldJudge->id)->count());
        $this->assertSame(1, $feedbacks->where(UserFeedback::FIELD_JUDGE_ID, $activeJudge->id)->count());
    }

    public function test_reuse_does_not_overwrite_active_human_lock_but_reuses_free_slot(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $grader = User::factory()->create();
        $locker = User::factory()->create();

        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);
        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'router',
        ]);
        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-router',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

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
            EvaluationKeyword::FIELD_KEYWORD => 'router',
        ]);
        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-router',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $slots = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->orderBy(UserFeedback::FIELD_ID)
            ->get();

        $slots[0]->update([
            UserFeedback::FIELD_USER_ID => $locker->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $this->service->apply($newEval);

        $slots = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->orderBy(UserFeedback::FIELD_ID)
            ->get();

        $this->assertSame($locker->id, $slots[0]->user_id);
        $this->assertNull($slots[0]->grade);

        $this->assertSame($grader->id, $slots[1]->user_id);
        $this->assertSame(BinaryScale::RELEVANT, $slots[1]->grade);
    }

    public function test_reuse_skips_feedback_from_deleted_user_and_deleted_judge(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $deletedUser = User::factory()->create();
        $deletedJudge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);

        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);
        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'usb',
        ]);
        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-usb',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $humanFeedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $deletedUser->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);
        $judgeFeedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $deletedJudge->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
            UserFeedback::FIELD_REASON => 'Deleted judge',
        ]);

        $deletedUser->delete();
        $deletedJudge->delete();
        $humanFeedback->refresh();
        $judgeFeedback->refresh();
        $this->assertNull($humanFeedback->user_id);
        $this->assertNull($judgeFeedback->judge_id);

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
            EvaluationKeyword::FIELD_KEYWORD => 'usb',
        ]);
        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-usb',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $this->service->apply($newEval);

        $newFeedback = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->first();

        $this->assertNull($newFeedback->user_id);
        $this->assertNull($newFeedback->judge_id);
        $this->assertNull($newFeedback->grade);
    }

    public function test_reuse_copies_reason_for_human_query_doc_and_judge_query_doc_position(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $grader = User::factory()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);

        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);
        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'ssd',
        ]);

        $oldSnapshotDoc = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-ssd',
            SearchSnapshot::FIELD_POSITION => 2,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshotDoc->id,
            UserFeedback::FIELD_USER_ID => $grader->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            UserFeedback::FIELD_REASON => 'Human reason',
        ]);

        $oldSnapshotPos = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-ssd-pos',
            SearchSnapshot::FIELD_POSITION => 5,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshotPos->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
            UserFeedback::FIELD_REASON => 'Judge positional reason',
        ]);

        $newEvalDoc = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
        $newKeywordDoc = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEvalDoc->id,
            EvaluationKeyword::FIELD_KEYWORD => 'ssd',
        ]);
        $newSnapshotDoc = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeywordDoc->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-ssd',
            SearchSnapshot::FIELD_POSITION => 999,
        ]);

        $newEvalPos = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC_POSITION,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
        $newKeywordPos = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEvalPos->id,
            EvaluationKeyword::FIELD_KEYWORD => 'ssd',
        ]);
        $newSnapshotPos = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeywordPos->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-ssd-pos',
            SearchSnapshot::FIELD_POSITION => 5,
        ]);

        $this->service->apply($newEvalDoc);
        $this->service->apply($newEvalPos);

        $docFeedback = UserFeedback::query()->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshotDoc->id)->first();
        $posFeedback = UserFeedback::query()->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshotPos->id)->first();

        $this->assertSame('Human reason', $docFeedback->reason);
        $this->assertSame($grader->id, $docFeedback->user_id);

        $this->assertSame('Judge positional reason', $posFeedback->reason);
        $this->assertSame($judge->id, $posFeedback->judge_id);
    }

    public function test_reuse_is_deterministic_when_multiple_candidates_have_same_weight(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $graderFirst = User::factory()->create();
        $graderSecond = User::factory()->create();

        $oldEvalFirst = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);
        $oldKeywordFirst = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEvalFirst->id,
            EvaluationKeyword::FIELD_KEYWORD => 'deterministic',
        ]);
        $oldSnapshotFirst = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeywordFirst->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-det',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshotFirst->id,
            UserFeedback::FIELD_USER_ID => $graderFirst->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            UserFeedback::FIELD_REASON => 'first',
        ]);

        $oldEvalSecond = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);
        $oldKeywordSecond = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEvalSecond->id,
            EvaluationKeyword::FIELD_KEYWORD => 'deterministic',
        ]);
        $oldSnapshotSecond = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeywordSecond->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-det',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshotSecond->id,
            UserFeedback::FIELD_USER_ID => $graderSecond->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
            UserFeedback::FIELD_REASON => 'second',
        ]);

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
            EvaluationKeyword::FIELD_KEYWORD => 'deterministic',
        ]);
        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-det',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $this->service->apply($newEval);

        $feedback = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->first();

        // Current deterministic behavior: array_pop() picks the last merged candidate.
        $this->assertSame($graderSecond->id, $feedback->user_id);
        $this->assertSame(BinaryScale::IRRELEVANT, $feedback->grade);
        $this->assertSame('second', $feedback->reason);
    }
}
