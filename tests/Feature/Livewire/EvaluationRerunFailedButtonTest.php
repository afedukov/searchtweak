<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Evaluation;
use App\Models\EvaluationKeyword;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\User;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EvaluationRerunFailedButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_button_is_visible_for_active_evaluations_with_failed_keywords(): void
    {
        [$user, $evaluation] = $this->createSetup(SearchEvaluation::STATUS_ACTIVE, 1);

        $this->actingAs($user);

        Livewire::test(Evaluation::class, ['evaluation' => $evaluation])
            ->assertSee('Rerun failed keywords');
    }

    public function test_button_is_hidden_without_failed_keywords(): void
    {
        [$user, $evaluation] = $this->createSetup(SearchEvaluation::STATUS_ACTIVE, 0);

        $this->actingAs($user);

        Livewire::test(Evaluation::class, ['evaluation' => $evaluation])
            ->assertDontSee('Rerun failed keywords');
    }

    public function test_button_is_visible_for_pending_started_evaluations_with_failed_keywords(): void
    {
        [$user, $evaluation] = $this->createSetup(SearchEvaluation::STATUS_PENDING, 1, true);

        $this->actingAs($user);

        Livewire::test(Evaluation::class, ['evaluation' => $evaluation])
            ->assertSee('Rerun failed keywords');
    }

    public function test_button_is_hidden_for_finished_evaluations(): void
    {
        [$user, $evaluation] = $this->createSetup(SearchEvaluation::STATUS_FINISHED, 1);

        $this->actingAs($user);

        Livewire::test(Evaluation::class, ['evaluation' => $evaluation])
            ->assertDontSee('Rerun failed keywords');
    }

    /**
     * @return array{0: User, 1: SearchEvaluation}
     */
    private function createSetup(int $status, int $failedKeywords, bool $started = false): array
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
            SearchEvaluation::FIELD_STATUS => $status,
            SearchEvaluation::FIELD_MAX_NUM_RESULTS => $status === SearchEvaluation::STATUS_PENDING
                ? ($started ? 10 : null)
                : 10,
            SearchEvaluation::FIELD_FINISHED_AT => $status === SearchEvaluation::STATUS_FINISHED ? now() : null,
            SearchEvaluation::FIELD_FAILED_KEYWORDS => $failedKeywords,
        ]);

        EvaluationKeyword::factory()->count(max($failedKeywords, 1))->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_FAILED => $failedKeywords > 0,
            EvaluationKeyword::FIELD_EXECUTION_CODE => $failedKeywords > 0 ? 500 : 200,
        ]);

        return [$user, $evaluation];
    }
}
