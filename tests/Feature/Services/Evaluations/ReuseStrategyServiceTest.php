<?php

namespace Tests\Feature\Services\Evaluations;

use App\Jobs\Evaluations\ProcessJudgeEvaluationJob;
use App\Jobs\Evaluations\RecalculateMetricsJob;
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
use App\Services\Evaluations\UserFeedbackService;
use App\Services\Judges\AbstractJudgeHandler;
use App\Services\Judges\JudgeHandlerFactory;
use App\Services\Scorers\Scales\BinaryScale;
use GuzzleHttp\ClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Mirrors Judge::matchesEvaluation: a judge with a strict subset of evaluation tags
     * would be rejected at runtime, so its historical grades must not be reused either.
     */
    public function test_reuse_rejects_judge_with_strict_subset_of_evaluation_tags(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $tagA = \App\Models\Tag::factory()->create(['team_id' => $team->id]);
        $tagB = \App\Models\Tag::factory()->create(['team_id' => $team->id]);

        // Judge has only [A] but evaluation requires [A, B].
        $judgePartial = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);
        $judgePartial->tags()->attach($tagA->id);

        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);
        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'headphones',
        ]);
        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-hp',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judgePartial->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
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
        $newEval->tags()->attach([$tagA->id, $tagB->id]);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'headphones',
        ]);
        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-hp',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $this->service->apply($newEval);

        $feedback = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->first();

        $this->assertNotNull($feedback);
        $this->assertNull($feedback->judge_id, 'Judge with only a subset of evaluation tags must not be reused.');
        $this->assertNull($feedback->grade);
    }

    /**
     * A judge carrying extra tags beyond the evaluation's tags still covers all required tags
     * and must be eligible for reuse — same as at runtime.
     */
    public function test_reuse_accepts_judge_with_superset_of_evaluation_tags(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $tagA = \App\Models\Tag::factory()->create(['team_id' => $team->id]);
        $tagB = \App\Models\Tag::factory()->create(['team_id' => $team->id]);
        $tagC = \App\Models\Tag::factory()->create(['team_id' => $team->id]);

        // Judge has [A, B, C]; evaluation requires [A, B].
        $judgeSuper = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);
        $judgeSuper->tags()->attach([$tagA->id, $tagB->id, $tagC->id]);

        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);
        $oldKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'speaker',
        ]);
        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-spk',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judgeSuper->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            UserFeedback::FIELD_REASON => 'Superset judge',
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
        $newEval->tags()->attach([$tagA->id, $tagB->id]);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'speaker',
        ]);
        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-spk',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $this->service->apply($newEval);

        $feedback = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->first();

        $this->assertNotNull($feedback);
        $this->assertSame($judgeSuper->id, $feedback->judge_id);
        $this->assertSame(BinaryScale::RELEVANT, $feedback->grade);
    }

    /**
     * An untagged judge is rejected at runtime for any tagged evaluation (Judge.php:240-242).
     * Reuse must not bypass that rule.
     */
    public function test_reuse_rejects_untagged_judge_for_tagged_evaluation(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $tagA = \App\Models\Tag::factory()->create(['team_id' => $team->id]);

        $judgeNoTags = Judge::factory()->create([
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
            EvaluationKeyword::FIELD_KEYWORD => 'monitor',
        ]);
        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-mon',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judgeNoTags->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
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
        $newEval->tags()->attach($tagA->id);

        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'monitor',
        ]);
        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-mon',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $this->service->apply($newEval);

        $feedback = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->first();

        $this->assertNotNull($feedback);
        $this->assertNull($feedback->judge_id, 'Untagged judge must not be reused for a tagged evaluation.');
        $this->assertNull($feedback->grade);
    }

    /**
     * Cross-team isolation: a feedback from another team's finished evaluation
     * must NOT enter the reuse pool, even if keyword + doc_id match.
     */
    public function test_does_not_reuse_feedback_from_other_team(): void
    {
        [$user, $team, $model] = $this->createSetup();

        // Build a parallel team with its own user/endpoint/model and a finished evaluation
        // carrying graded feedback for the same keyword + doc_id.
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;
        $otherEndpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $otherUser->id,
            SearchEndpoint::FIELD_TEAM_ID => $otherTeam->id,
        ]);
        $otherModel = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $otherUser->id,
            SearchModel::FIELD_TEAM_ID => $otherTeam->id,
            SearchModel::FIELD_ENDPOINT_ID => $otherEndpoint->id,
        ]);

        $otherEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $otherUser->id,
            SearchEvaluation::FIELD_MODEL_ID => $otherModel->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
        $otherKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $otherEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'shared-keyword',
        ]);
        $otherSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $otherKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'shared-doc',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $otherSnapshot->id,
            UserFeedback::FIELD_USER_ID => $otherUser->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        // Current team's evaluation expecting the same query/doc — must NOT pick up the other team's grade.
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
            EvaluationKeyword::FIELD_KEYWORD => 'shared-keyword',
        ]);
        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'shared-doc',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        $feedback = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->first();

        $this->service->apply($newEval);

        $feedback->refresh();
        $this->assertNull($feedback->user_id, 'Other team\'s feedback must not leak across team boundary.');
        $this->assertNull($feedback->judge_id);
        $this->assertNull($feedback->grade);
    }

    /**
     * RecalculateMetricsJob is dispatched only for keywords whose feedbacks
     * were actually updated — keywords with no reuse hits must NOT trigger it.
     */
    public function test_dispatches_recalculate_metrics_only_for_updated_keywords(): void
    {
        Bus::fake();

        [$user, $team, $model] = $this->createSetup();

        // Source: graded feedback exists only for keyword 'matched', not for 'unmatched'.
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
            EvaluationKeyword::FIELD_KEYWORD => 'matched',
        ]);
        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-1',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        $grader = User::factory()->create();
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
            UserFeedback::FIELD_USER_ID => $grader->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        // New eval with two keywords; only 'matched' should trigger recalculation.
        $newEval = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
        $matchedKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'matched',
        ]);
        SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $matchedKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-1',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        $unmatchedKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'unmatched',
        ]);
        SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $unmatchedKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-1',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $this->service->apply($newEval);

        Bus::assertDispatchedTimes(RecalculateMetricsJob::class, 1);
        Bus::assertDispatched(
            RecalculateMetricsJob::class,
            fn (RecalculateMetricsJob $job) => (int) $job->uniqueId() === $matchedKeyword->id,
        );
    }

    /**
     * When at least one feedback is actually reused, the team's
     * ungraded-snapshots-count cache must be flushed so evaluators see updated counters.
     */
    public function test_flushes_ungraded_snapshots_cache_when_updates_happen(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $cacheTag = UserFeedbackService::getUngradedSnapshotsCountCacheTag($team->id);
        $sentinelKey = 'reuse-test-sentinel';
        Cache::tags($cacheTag)->put($sentinelKey, 'present', 300);
        $this->assertSame('present', Cache::tags($cacheTag)->get($sentinelKey));

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
            EvaluationKeyword::FIELD_KEYWORD => 'cache-key',
        ]);
        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-cache',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        $grader = User::factory()->create();
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
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'cache-key',
        ]);
        SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-cache',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $this->service->apply($newEval);

        $this->assertNull(
            Cache::tags($cacheTag)->get($sentinelKey),
            'Ungraded-snapshots cache for the team must be flushed after reuse updates.',
        );
    }

    /**
     * Keyword text matching must be exact — strings differing only by case
     * must not collide. Locks in current behavior so a future SQL-based
     * rewrite cannot silently introduce case-insensitive matching.
     */
    public function test_keyword_text_matching_is_exact_and_case_sensitive(): void
    {
        [$user, $team, $model] = $this->createSetup();

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
            EvaluationKeyword::FIELD_KEYWORD => 'LAPTOP',
        ]);
        $oldSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-case',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        $grader = User::factory()->create();
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
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
        $newKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
            EvaluationKeyword::FIELD_KEYWORD => 'laptop',
        ]);
        $newSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-case',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        $feedback = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
            ->first();

        $this->service->apply($newEval);

        $feedback->refresh();
        $this->assertNull($feedback->user_id, '"laptop" must not match grades stored under "LAPTOP".');
        $this->assertNull($feedback->grade);
    }

    /**
     * Reuse processes the current eval's keywords in fixed-size chunks
     * (KEYWORD_CHUNK_SIZE = 10) to bound peak memory. This test crosses the
     * chunk boundary with 15 keywords (1 full chunk + 1 partial) to make sure
     * a future regression cannot silently break chunking — every keyword must
     * still end up reused and metric-recalc-dispatched.
     */
    public function test_reuse_processes_correctly_across_chunks(): void
    {
        Bus::fake();

        [$user, $team, $model] = $this->createSetup();

        $oldEval = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
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

        $grader = User::factory()->create();
        $newSnapshots = [];
        $keywordCount = 15;

        for ($i = 1; $i <= $keywordCount; $i++) {
            $keywordText = sprintf('chunk-keyword-%02d', $i);
            $docId = sprintf('chunk-doc-%02d', $i);

            $oldKeyword = EvaluationKeyword::factory()->create([
                EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $oldEval->id,
                EvaluationKeyword::FIELD_KEYWORD => $keywordText,
            ]);
            $oldSnapshot = SearchSnapshot::factory()->create([
                SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $oldKeyword->id,
                SearchSnapshot::FIELD_DOC_ID => $docId,
                SearchSnapshot::FIELD_POSITION => 1,
            ]);
            UserFeedback::factory()->create([
                UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $oldSnapshot->id,
                UserFeedback::FIELD_USER_ID => $grader->id,
                UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            ]);

            $newKeyword = EvaluationKeyword::factory()->create([
                EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $newEval->id,
                EvaluationKeyword::FIELD_KEYWORD => $keywordText,
            ]);
            $newSnapshots[] = SearchSnapshot::factory()->create([
                SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $newKeyword->id,
                SearchSnapshot::FIELD_DOC_ID => $docId,
                SearchSnapshot::FIELD_POSITION => 1,
            ]);
        }

        $this->service->apply($newEval);

        // Every chunk's keyword should have been processed: each new feedback
        // is now graded and one RecalculateMetricsJob per keyword was dispatched.
        foreach ($newSnapshots as $newSnapshot) {
            $feedback = UserFeedback::query()
                ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $newSnapshot->id)
                ->first();
            $this->assertNotNull($feedback);
            $this->assertSame($grader->id, $feedback->user_id);
            $this->assertSame(BinaryScale::RELEVANT, $feedback->grade);
        }

        Bus::assertDispatchedTimes(RecalculateMetricsJob::class, $keywordCount);
    }
}
