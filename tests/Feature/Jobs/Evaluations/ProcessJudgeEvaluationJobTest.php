<?php

namespace Tests\Feature\Jobs\Evaluations;

use App\Jobs\Evaluations\ProcessJudgeEvaluationJob;
use App\Models\EvaluationKeyword;
use App\Models\Judge;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Evaluations\UserFeedbackService;
use App\Services\Judges\AbstractJudgeHandler;
use App\Services\Judges\JudgeHandlerFactory;
use App\Services\Scorers\Scales\BinaryScale;
use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class ProcessJudgeEvaluationJobTest extends TestCase
{
    use RefreshDatabase;

    private function createEvaluationSetup(): array
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
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 3,
            ],
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_KEYWORD => 'kettle',
        ]);

        $snapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-1',
            SearchSnapshot::FIELD_NAME => 'Doc 1',
            SearchSnapshot::FIELD_DOC => [],
        ]);

        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_PROVIDER => Judge::PROVIDER_OPENAI,
            Judge::FIELD_MODEL_NAME => 'gpt-4',
        ]);

        return [$user, $evaluation, $snapshot, $judge];
    }

    public function test_judge_cannot_grade_second_slot_for_same_snapshot_in_multiple_strategy(): void
    {
        [, $evaluation, $snapshot, $judge] = $this->createEvaluationSetup();

        $feedbacks = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshot->id)
            ->orderBy(UserFeedback::FIELD_ID)
            ->get();

        $this->assertCount(3, $feedbacks);

        $feedbacks[0]->update([
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            UserFeedback::FIELD_REASON => 'Existing judge grade',
        ]);

        $factory = Mockery::mock(JudgeHandlerFactory::class);
        $factory->shouldNotReceive('create');

        (new ProcessJudgeEvaluationJob($evaluation->id))->handle($factory);

        $snapshotFeedbacks = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshot->id)
            ->orderBy(UserFeedback::FIELD_ID)
            ->get();

        $this->assertSame(1, $snapshotFeedbacks->where(UserFeedback::FIELD_JUDGE_ID, $judge->id)->count());
        $this->assertSame(1, $snapshotFeedbacks->whereNotNull(UserFeedback::FIELD_GRADE)->count());
        $this->assertSame(2, $snapshotFeedbacks->whereNull(UserFeedback::FIELD_GRADE)->count());
    }

    public function test_job_redispatches_with_delay_when_mixed_lock_expiry_exists_in_multiple_strategy(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-19 12:00:00'));

        [, $evaluation, $snapshot, $judge] = $this->createEvaluationSetup();

        $activeLockedUser = User::factory()->withPersonalTeam()->create();
        $expiredLockedUser = User::factory()->withPersonalTeam()->create();

        $feedbacks = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshot->id)
            ->orderBy(UserFeedback::FIELD_ID)
            ->get();

        $this->assertCount(3, $feedbacks);

        // Slot 1: already graded by this judge (so this judge cannot take any other slot for this snapshot).
        $feedbacks[0]->update([
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            UserFeedback::FIELD_REASON => 'Existing judge grade',
        ]);

        // Slot 2: human-locked and still active.
        $feedbacks[1]->update([
            UserFeedback::FIELD_USER_ID => $activeLockedUser->id,
            UserFeedback::FIELD_JUDGE_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        // Slot 3: human lock expired.
        $feedbacks[2]->update([
            UserFeedback::FIELD_USER_ID => $expiredLockedUser->id,
            UserFeedback::FIELD_JUDGE_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);
        $feedbacks[2]->updated_at = now()->subMinutes(UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES + 1);
        $feedbacks[2]->saveQuietly();

        Bus::fake();

        $factory = Mockery::mock(JudgeHandlerFactory::class);
        $factory->shouldNotReceive('create');

        (new ProcessJudgeEvaluationJob($evaluation->id))->handle($factory);

        Bus::assertDispatched(ProcessJudgeEvaluationJob::class, function (ProcessJudgeEvaluationJob $job) use ($evaluation) {
            return $job->uniqueId() === (string) $evaluation->id
                && $job->delay instanceof \DateTimeInterface
                && Carbon::instance($job->delay)->equalTo(now()->addMinutes(UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES + 1));
        });

        Carbon::setTestNow();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_single_judge_claims_only_one_slot_per_snapshot_even_with_large_batch_size(): void
    {
        [, $evaluation, $snapshot, $judge] = $this->createEvaluationSetup();

        $judge->settings = [
            Judge::SETTING_BATCH_SIZE => 5,
        ];
        $judge->save();

        $handler = new class(Mockery::mock(ClientInterface::class)) extends AbstractJudgeHandler {
            public int $calls = 0;

            public function grade(Judge $judge, string $prompt, array $validGrades): array
            {
                $this->calls++;

                return [[
                    'pair_index' => 0,
                    'grade' => BinaryScale::RELEVANT,
                    'reason' => 'Relevant',
                ]];
            }
        };

        $factory = Mockery::mock(JudgeHandlerFactory::class);
        $factory->shouldReceive('create')->once()->withArgs(function (Judge $argJudge) use ($judge) {
            return $argJudge->id === $judge->id;
        })->andReturn($handler);

        (new ProcessJudgeEvaluationJob($evaluation->id))->handle($factory);

        $snapshotFeedbacks = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshot->id)
            ->orderBy(UserFeedback::FIELD_ID)
            ->get();

        $this->assertSame(1, $handler->calls);
        $this->assertCount(3, $snapshotFeedbacks);
        $this->assertSame(1, $snapshotFeedbacks->where(UserFeedback::FIELD_JUDGE_ID, $judge->id)->count());
        $this->assertSame(1, $snapshotFeedbacks->whereNotNull(UserFeedback::FIELD_GRADE)->count());
        $this->assertSame(2, $snapshotFeedbacks->whereNull(UserFeedback::FIELD_GRADE)->count());
    }
}
