<?php

namespace Tests\Feature\Jobs\Evaluations;

use App\DTO\ModelExecutionResult;
use App\Jobs\Evaluations\ProcessKeywordJob;
use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\KeywordMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Services\Models\ExecuteModelService;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class ProcessKeywordJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_rerun_mode_resets_failed_keyword_before_processing(): void
    {
        [$user, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);

        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]);

        $failedKeyword = EvaluationKeyword::factory()->failed()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $failedSnapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $failedKeyword->id,
        ]);

        $failedKeywordMetric = KeywordMetric::create([
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $failedKeyword->id,
            KeywordMetric::FIELD_EVALUATION_METRIC_ID => $metric->id,
            KeywordMetric::FIELD_VALUE => 0.5,
        ]);

        $executeModelService = Mockery::mock(ExecuteModelService::class);
        $executeModelService->shouldReceive('initialize')
            ->once()
            ->withArgs(fn (SearchModel $argument) => $argument->id === $model->id)
            ->andReturnSelf();
        $executeModelService->shouldReceive('setLimit')
            ->once()
            ->with(10)
            ->andReturnSelf();
        $executeModelService->shouldReceive('execute')
            ->once()
            ->with($failedKeyword->keyword)
            ->andReturn(new ModelExecutionResult(200, 'OK', new Collection(), 0));

        (new ProcessKeywordJob($failedKeyword->id, resetBeforeProcessing: true))->handle($executeModelService);

        $failedKeyword->refresh();

        $this->assertSame(200, $failedKeyword->execution_code);
        $this->assertSame('OK', $failedKeyword->execution_message);
        $this->assertSame(0, $failedKeyword->total_count);
        $this->assertFalse($failedKeyword->failed);

        $this->assertDatabaseMissing('search_snapshots', [
            SearchSnapshot::FIELD_ID => $failedSnapshot->id,
        ]);
        $this->assertDatabaseMissing('keyword_metrics', [
            KeywordMetric::FIELD_ID => $failedKeywordMetric->id,
        ]);
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
