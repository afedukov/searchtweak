<?php

namespace Tests\Feature\Jobs\Evaluations;

use App\Jobs\Evaluations\PostStartEvaluationJob;
use App\Jobs\Evaluations\ProcessJudgeEvaluationJob;
use App\Models\EvaluationKeyword;
use App\Models\Judge;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Evaluations\ReuseStrategyService;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Bus\Dispatcher;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class PostStartEvaluationJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The lifecycle invariant of the fix: evaluation must NOT advance to ACTIVE
     * if reuse fails midway. Otherwise the next retry short-circuits on the
     * `isPending()` guard and leaves the eval half-processed (no judge dispatch).
     */
    public function test_status_stays_pending_when_reuse_throws(): void
    {
        [$evaluation] = $this->createPendingEvaluationWithReuseStrategy();
        $evaluation->blockChanges();

        $reuseStrategy = Mockery::mock(ReuseStrategyService::class);
        $reuseStrategy->shouldReceive('apply')
            ->once()
            ->andThrow(new \RuntimeException('reuse died mid-way'));

        try {
            (new PostStartEvaluationJob($evaluation->id))->handle($reuseStrategy);
            $this->fail('Expected RuntimeException to be re-thrown for retry.');
        } catch (\RuntimeException $e) {
            $this->assertSame('reuse died mid-way', $e->getMessage());
        }

        $evaluation->refresh();
        $this->assertSame(
            SearchEvaluation::STATUS_PENDING,
            $evaluation->status,
            'Evaluation must stay pending so the retry re-enters the job instead of short-circuiting.',
        );
        $this->assertFalse(
            $evaluation->changes_blocked,
            'UI changes-blocked flag must be released even when the job throws.',
        );
    }

    /**
     * Regression: when the eval is no longer pending on entry (e.g. a previous
     * attempt set it active before crashing under the old code), `handle()`
     * must still release the UI changes-blocked flag in the `finally` block.
     */
    public function test_releases_changes_block_when_evaluation_no_longer_pending(): void
    {
        [$evaluation] = $this->createPendingEvaluationWithReuseStrategy();
        $evaluation->status = SearchEvaluation::STATUS_ACTIVE;
        $evaluation->save();
        $evaluation->blockChanges();

        $reuseStrategy = Mockery::mock(ReuseStrategyService::class);
        $reuseStrategy->shouldNotReceive('apply');

        (new PostStartEvaluationJob($evaluation->id))->handle($reuseStrategy);

        $evaluation->refresh();
        $this->assertSame(SearchEvaluation::STATUS_ACTIVE, $evaluation->status);
        $this->assertFalse(
            $evaluation->changes_blocked,
            'UI changes-blocked flag must be released on the early-return path too.',
        );
    }

    /**
     * On a successful run, the eval must end up ACTIVE, the UI flag released,
     * and judge processing dispatched when matching active judges exist.
     */
    public function test_successful_run_marks_evaluation_active_and_dispatches_judge(): void
    {
        Bus::fake();

        [$evaluation, $user] = $this->createPendingEvaluationWithReuseStrategy();
        // Untagged judge matches an untagged evaluation.
        Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_PROVIDER => Judge::PROVIDER_OPENAI,
            Judge::FIELD_MODEL_NAME => 'gpt-4',
        ]);
        $evaluation->blockChanges();

        $reuseStrategy = Mockery::mock(ReuseStrategyService::class);
        $reuseStrategy->shouldReceive('apply')->once();

        (new PostStartEvaluationJob($evaluation->id))->handle($reuseStrategy);

        $evaluation->refresh();
        $this->assertSame(SearchEvaluation::STATUS_ACTIVE, $evaluation->status);
        $this->assertFalse($evaluation->changes_blocked);
        Bus::assertDispatched(ProcessJudgeEvaluationJob::class);
    }

    /**
     * If the eval has no matching judges, judge job must NOT be dispatched.
     */
    public function test_does_not_dispatch_judge_when_no_matching_judges(): void
    {
        Bus::fake();

        [$evaluation] = $this->createPendingEvaluationWithReuseStrategy();
        $evaluation->blockChanges();

        $reuseStrategy = Mockery::mock(ReuseStrategyService::class);
        $reuseStrategy->shouldReceive('apply')->once();

        (new PostStartEvaluationJob($evaluation->id))->handle($reuseStrategy);

        $evaluation->refresh();
        $this->assertSame(SearchEvaluation::STATUS_ACTIVE, $evaluation->status);
        Bus::assertNotDispatched(ProcessJudgeEvaluationJob::class);
    }

    /**
     * The same lifecycle invariant must hold one step further down the
     * pipeline: if judge dispatch throws AFTER reuse succeeded, status must
     * NOT advance to ACTIVE. Otherwise the retry hits `!isPending()` and
     * silently skips judge-phase recovery.
     */
    public function test_status_stays_pending_when_judge_dispatch_throws(): void
    {
        [$evaluation, $user] = $this->createPendingEvaluationWithReuseStrategy();
        // Matching judge so the dispatch is actually attempted.
        Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_PROVIDER => Judge::PROVIDER_OPENAI,
            Judge::FIELD_MODEL_NAME => 'gpt-4',
        ]);
        $evaluation->blockChanges();

        $reuseStrategy = Mockery::mock(ReuseStrategyService::class);
        $reuseStrategy->shouldReceive('apply')->once();

        $this->app->bind(DispatcherContract::class, fn ($app) => new class($app) extends Dispatcher {
            public function dispatch($command)
            {
                if ($command instanceof ProcessJudgeEvaluationJob) {
                    throw new \RuntimeException('judge dispatch failed');
                }
                return parent::dispatch($command);
            }
        });

        try {
            (new PostStartEvaluationJob($evaluation->id))->handle($reuseStrategy);
            $this->fail('Expected RuntimeException to be re-thrown for retry.');
        } catch (\RuntimeException $e) {
            $this->assertSame('judge dispatch failed', $e->getMessage());
        }

        $evaluation->refresh();
        $this->assertSame(
            SearchEvaluation::STATUS_PENDING,
            $evaluation->status,
            'Status must stay PENDING when judge dispatch throws so retry can re-enter and complete startup.',
        );
        $this->assertFalse($evaluation->changes_blocked);
    }

    /**
     * After all retries are exhausted the trait-provided `failed()` hook
     * must still release the UI flag — covers worker SIGTERM paths where
     * the PHP `finally` does not run.
     */
    public function test_failed_hook_releases_changes_block(): void
    {
        [$evaluation] = $this->createPendingEvaluationWithReuseStrategy();
        $evaluation->blockChanges();

        (new PostStartEvaluationJob($evaluation->id))->failed(new \RuntimeException('worker killed'));

        $evaluation->refresh();
        $this->assertFalse($evaluation->changes_blocked);
    }

    /**
     * @return array{0: SearchEvaluation, 1: User}
     */
    private function createPendingEvaluationWithReuseStrategy(): array
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
        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_KEYWORD => 'sample',
        ]);
        $snapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-sample',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshot->id)
            ->update([
                UserFeedback::FIELD_USER_ID => null,
                UserFeedback::FIELD_GRADE => null,
            ]);

        return [$evaluation, $user];
    }
}
