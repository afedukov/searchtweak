<?php

namespace Tests\Feature\Actions\Evaluations;

use App\Actions\Evaluations\RerunFailedEvaluationKeywords;
use App\Jobs\Evaluations\ProcessKeywordJob;
use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\KeywordMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RerunFailedEvaluationKeywordsTest extends TestCase
{
    use RefreshDatabase;

    private RerunFailedEvaluationKeywords $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(RerunFailedEvaluationKeywords::class);
    }

    public function test_rerun_dispatches_only_failed_keywords_without_resetting_them_before_jobs_are_queued(): void
    {
        Bus::fake();

        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
            SearchEvaluation::FIELD_PROGRESS => 100,
            SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => 1,
            SearchEvaluation::FIELD_FAILED_KEYWORDS => 1,
        ]);

        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_VALUE => 0.75,
        ]);

        $successfulKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_KEYWORD => 'successful keyword',
            EvaluationKeyword::FIELD_EXECUTION_CODE => 200,
            EvaluationKeyword::FIELD_EXECUTION_MESSAGE => 'OK',
            EvaluationKeyword::FIELD_FAILED => false,
        ]);
        $failedKeyword = EvaluationKeyword::factory()->failed()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_KEYWORD => 'failed keyword',
        ]);

        $successfulSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $successfulKeyword->id,
        ]);
        $failedSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $failedKeyword->id,
        ]);

        $successfulSnapshot->feedbacks()->first()->forceFill([
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ])->saveQuietly();

        $failedSnapshot->feedbacks()->first()->forceFill([
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ])->saveQuietly();

        $successfulKeywordMetric = KeywordMetric::create([
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $successfulKeyword->id,
            KeywordMetric::FIELD_EVALUATION_METRIC_ID => $metric->id,
            KeywordMetric::FIELD_VALUE => 1.0,
        ]);
        $failedKeywordMetric = KeywordMetric::create([
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $failedKeyword->id,
            KeywordMetric::FIELD_EVALUATION_METRIC_ID => $metric->id,
            KeywordMetric::FIELD_VALUE => 0.5,
        ]);

        $this->action->rerun($evaluation);

        $successfulKeyword->refresh();
        $failedKeyword->refresh();
        $evaluation->refresh();
        $metric->refresh();

        $this->assertSame(200, $successfulKeyword->execution_code);
        $this->assertFalse($successfulKeyword->failed);
        $this->assertSame(500, $failedKeyword->execution_code);
        $this->assertTrue($failedKeyword->failed);
        $this->assertSame(0.75, $metric->value);
        $this->assertSame(100.0, $evaluation->progress);

        $this->assertDatabaseHas('search_snapshots', [
            SearchSnapshot::FIELD_ID => $successfulSnapshot->id,
        ]);
        $this->assertDatabaseHas('search_snapshots', [
            SearchSnapshot::FIELD_ID => $failedSnapshot->id,
        ]);
        $this->assertDatabaseHas('keyword_metrics', [
            KeywordMetric::FIELD_ID => $successfulKeywordMetric->id,
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $successfulKeyword->id,
        ]);
        $this->assertDatabaseHas('keyword_metrics', [
            KeywordMetric::FIELD_ID => $failedKeywordMetric->id,
        ]);

        Bus::assertBatched(function (PendingBatch $batch) use ($evaluation, $failedKeyword) {
            if ($batch->name !== sprintf('Rerun Failed Keywords %d', $evaluation->id)) {
                return false;
            }

            if (count($batch->jobs) !== 1 || !$batch->jobs[0] instanceof ProcessKeywordJob) {
                return false;
            }

            $reflection = new \ReflectionProperty($batch->jobs[0], 'keywordId');
            $resetReflection = new \ReflectionProperty($batch->jobs[0], 'resetBeforeProcessing');

            return $reflection->getValue($batch->jobs[0]) === $failedKeyword->id
                && $resetReflection->getValue($batch->jobs[0]) === true;
        });
    }

    public function test_rerun_allows_pending_started_evaluation(): void
    {
        Bus::fake();

        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING,
            SearchEvaluation::FIELD_MAX_NUM_RESULTS => 10,
        ]);

        $failedKeyword = EvaluationKeyword::factory()->failed()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $this->action->rerun($evaluation);

        Bus::assertBatched(function (PendingBatch $batch) use ($evaluation, $failedKeyword) {
            if ($batch->name !== sprintf('Rerun Failed Keywords %d', $evaluation->id)) {
                return false;
            }

            $reflection = new \ReflectionProperty($batch->jobs[0], 'keywordId');

            return count($batch->jobs) === 1 && $reflection->getValue($batch->jobs[0]) === $failedKeyword->id;
        });
    }

    public function test_rerun_requires_started_non_finished_evaluation(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        EvaluationKeyword::factory()->failed()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('evaluation must be active or pending');

        $this->action->rerun($evaluation);
    }

    public function test_rerun_requires_started_evaluation(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING,
            SearchEvaluation::FIELD_MAX_NUM_RESULTS => null,
        ]);

        EvaluationKeyword::factory()->failed()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('evaluation has not started');

        $this->action->rerun($evaluation);
    }

    public function test_rerun_requires_failed_keywords(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('evaluation has no failed keywords');

        $this->action->rerun($evaluation);
    }

    /**
     * @return array{0: User, 1: SearchModel}
     */
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

        return [$user, $model];
    }
}
