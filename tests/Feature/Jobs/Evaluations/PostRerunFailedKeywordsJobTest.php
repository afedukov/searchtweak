<?php

namespace Tests\Feature\Jobs\Evaluations;

use App\Events\EvaluationProgressChangedEvent;
use App\Events\EvaluationKeywordsChangedEvent;
use App\Jobs\Evaluations\PostRerunFailedKeywordsJob;
use App\Jobs\Evaluations\ProcessJudgeEvaluationJob;
use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\Judge;
use App\Models\KeywordMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Evaluations\EvaluationExecutionService;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class PostRerunFailedKeywordsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_counts_progress_and_dispatches_judges(): void
    {
        Bus::fake();

        [$user, $evaluation] = $this->createSetup();

        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_VALUE => 0.25,
        ]);

        $successfulKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_EXECUTION_CODE => 200,
            EvaluationKeyword::FIELD_FAILED => false,
        ]);
        $failedKeyword = EvaluationKeyword::factory()->failed()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $successfulSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $successfulKeyword->id,
        ]);
        SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $failedKeyword->id,
        ]);

        $successfulSnapshot->feedbacks()->first()->forceFill([
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ])->saveQuietly();

        KeywordMetric::create([
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $successfulKeyword->id,
            KeywordMetric::FIELD_EVALUATION_METRIC_ID => $metric->id,
            KeywordMetric::FIELD_VALUE => 1.0,
        ]);

        Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_PROVIDER => Judge::PROVIDER_OPENAI,
            Judge::FIELD_MODEL_NAME => 'gpt-4',
        ]);

        $evaluation->blockChanges();

        (new PostRerunFailedKeywordsJob($evaluation->id))->handle(app(EvaluationExecutionService::class));

        $evaluation->refresh();
        $metric->refresh();

        $this->assertSame(1, $evaluation->successful_keywords);
        $this->assertSame(1, $evaluation->failed_keywords);
        $this->assertSame(50.0, $evaluation->progress);
        $this->assertSame(1.0, $metric->value);
        $this->assertFalse($evaluation->changes_blocked);

        Bus::assertDispatched(ProcessJudgeEvaluationJob::class);
    }

    public function test_releases_changes_block_when_evaluation_is_no_longer_active(): void
    {
        [, $evaluation] = $this->createSetup();

        $evaluation->status = SearchEvaluation::STATUS_FINISHED;
        $evaluation->save();
        $evaluation->blockChanges();

        (new PostRerunFailedKeywordsJob($evaluation->id))->handle(app(EvaluationExecutionService::class));

        $evaluation->refresh();

        $this->assertFalse($evaluation->changes_blocked);
    }

    public function test_pending_evaluation_stays_pending_after_rerun(): void
    {
        Bus::fake();

        [$user, $evaluation] = $this->createSetup();

        $evaluation->status = SearchEvaluation::STATUS_PENDING;
        $evaluation->save();

        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]);

        $successfulKeyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_EXECUTION_CODE => 200,
            EvaluationKeyword::FIELD_FAILED => false,
        ]);

        $snapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $successfulKeyword->id,
        ]);

        $snapshot->feedbacks()->first()->forceFill([
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ])->saveQuietly();

        KeywordMetric::create([
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $successfulKeyword->id,
            KeywordMetric::FIELD_EVALUATION_METRIC_ID => $metric->id,
            KeywordMetric::FIELD_VALUE => 1.0,
        ]);

        $evaluation->blockChanges();

        (new PostRerunFailedKeywordsJob($evaluation->id))->handle(app(EvaluationExecutionService::class));

        $evaluation->refresh();

        $this->assertTrue($evaluation->isPending());
        $this->assertFalse($evaluation->changes_blocked);
        Bus::assertNotDispatched(ProcessJudgeEvaluationJob::class);
    }

    public function test_dispatches_progress_changed_event_even_when_progress_value_does_not_change(): void
    {
        Bus::fake();

        [, $evaluation] = $this->createSetup();
        $evaluation->blockChanges();

        Event::fake([EvaluationProgressChangedEvent::class]);

        (new PostRerunFailedKeywordsJob($evaluation->id))->handle(app(EvaluationExecutionService::class));

        Event::assertDispatched(
            EvaluationProgressChangedEvent::class,
            fn (EvaluationProgressChangedEvent $event) => $event->broadcastWith()['id'] === $evaluation->id,
        );
    }

    public function test_dispatches_keywords_changed_event_after_rerun_post_processing(): void
    {
        Bus::fake();

        [, $evaluation] = $this->createSetup();
        $evaluation->blockChanges();

        Event::fake([EvaluationKeywordsChangedEvent::class]);

        (new PostRerunFailedKeywordsJob($evaluation->id))->handle(app(EvaluationExecutionService::class));

        Event::assertDispatched(
            EvaluationKeywordsChangedEvent::class,
            fn (EvaluationKeywordsChangedEvent $event) => $event->broadcastWith() === [
                'id' => $evaluation->id,
            ],
        );
    }

    public function test_unexpected_failure_is_rethrown_for_retry_and_releases_changes_block(): void
    {
        [, $evaluation] = $this->createSetup();
        $evaluation->blockChanges();

        $service = Mockery::mock(EvaluationExecutionService::class);
        $service->shouldReceive('syncProcessedKeywordCounts')
            ->once()
            ->andThrow(new \RuntimeException('count sync failed'));

        try {
            (new PostRerunFailedKeywordsJob($evaluation->id))->handle($service);
            $this->fail('Expected RuntimeException to be re-thrown for retry.');
        } catch (\RuntimeException $e) {
            $this->assertSame('count sync failed', $e->getMessage());
        }

        $evaluation->refresh();

        $this->assertFalse($evaluation->changes_blocked);
    }

    /**
     * @return array{0: User, 1: SearchEvaluation}
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

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
            SearchEvaluation::FIELD_PROGRESS => 0,
            SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => 0,
            SearchEvaluation::FIELD_FAILED_KEYWORDS => 0,
        ]);

        return [$user, $evaluation];
    }
}
