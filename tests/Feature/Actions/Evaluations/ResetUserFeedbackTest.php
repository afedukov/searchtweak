<?php

namespace Tests\Feature\Actions\Evaluations;

use App\Actions\Evaluations\ResetUserFeedback;
use App\Models\EvaluationKeyword;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Policies\Roles;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetUserFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private ResetUserFeedback $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ResetUserFeedback();
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

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $snapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
        ]);

        return [$user, $snapshot];
    }

    public function test_reset_clears_specific_feedback(): void
    {
        [$user, $snapshot] = $this->createSetup();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => 1,
            UserFeedback::FIELD_UPDATED_AT => now(),
        ]);

        $this->actingAs($user);
        $this->action->reset($snapshot->keyword->evaluation, $feedback);

        $feedback->refresh();
        $this->assertNull($feedback->grade);
        $this->assertNull($feedback->user_id);
    }

    public function test_reset_fails_if_evaluation_finished(): void
    {
        [$user, $snapshot] = $this->createSetup();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => 1,
        ]);
        
        $snapshot->keyword->evaluation->update([
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_FINISHED
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('evaluation is finished');

        $this->actingAs($user);
        $this->action->reset($snapshot->keyword->evaluation, $feedback);
    }

    public function test_reset_fails_without_permission(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        
        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'evaluator']); 
        $member->switchTeam($team);

        $endpoint = SearchEndpoint::factory()->create(['team_id' => $team->id]);
        $model = SearchModel::factory()->create(['team_id' => $team->id, 'endpoint_id' => $endpoint->id]);
        $evaluation = SearchEvaluation::factory()->active()->create(['model_id' => $model->id]);
        $keyword = EvaluationKeyword::factory()->create(['search_evaluation_id' => $evaluation->id]);
        $snapshot = SearchSnapshot::factory()->create(['evaluation_keyword_id' => $keyword->id]);
        
        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id, // Assigned to owner
            UserFeedback::FIELD_GRADE => 1,
        ]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->actingAs($member);
        $this->action->reset($snapshot->keyword->evaluation, $feedback);
    }
}
