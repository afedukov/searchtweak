<?php

namespace Tests\Feature\Actions\Evaluations;

use App\Actions\Evaluations\FinishSearchEvaluation;
use App\Models\EvaluationMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\User;
use App\Notifications\EvaluationFinishNotification;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FinishSearchEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private FinishSearchEvaluation $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(FinishSearchEvaluation::class);
        Queue::fake();
        Notification::fake();
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

        return [$user, $model];
    }

    public function test_finish_active_evaluation(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
        ]);

        $this->action->finish($evaluation);

        $evaluation->refresh();
        $metric->refresh();

        $this->assertTrue($evaluation->isFinished());
        $this->assertNotNull($evaluation->finished_at);
        $this->assertNotNull($metric->finished_at);
    }

    public function test_finish_sends_notification(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $this->action->finish($evaluation);

        Notification::assertSentTo($user, EvaluationFinishNotification::class);
    }

    public function test_finish_already_finished_throws(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('evaluation is already finished');

        $this->action->finish($evaluation);
    }

    public function test_finish_pending_evaluation(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING,
        ]);

        // Pending evaluation should be finishable
        $this->action->finish($evaluation);

        $evaluation->refresh();
        $this->assertTrue($evaluation->isFinished());
    }
}
