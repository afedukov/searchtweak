<?php

namespace Tests\Feature\Actions\Evaluations;

use App\Actions\Evaluations\ResetSearchSnapshot;
use App\Models\EvaluationKeyword;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Policies\Permissions;
use App\Policies\Roles;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetSearchSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private ResetSearchSnapshot $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ResetSearchSnapshot();
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

    public function test_reset_clears_user_grade_and_assigment(): void
    {
        [$user, $snapshot] = $this->createSetup();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => 1,
            UserFeedback::FIELD_UPDATED_AT => now(),
        ]);

        $this->action->reset($snapshot, $user);

        $feedback->refresh();
        $this->assertNull($feedback->grade);
        $this->assertNull($feedback->user_id);
    }

    public function test_reset_ignores_ungraded_feedback(): void
    {
        [$user, $snapshot] = $this->createSetup();

        $feedback = UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => null, // Not graded yet
            UserFeedback::FIELD_UPDATED_AT => now(),
        ]);

        // Should not modify ungraded feedback because reset() specifically looks for graded feedback
        $this->action->reset($snapshot, $user);

        $feedback->refresh();
        $this->assertEquals($user->id, $feedback->user_id); // Still assigned
        $this->assertNull($feedback->grade);
    }

    public function test_reset_fails_if_evaluation_finished(): void
    {
        [$user, $snapshot] = $this->createSetup();
        
        $snapshot->keyword->evaluation->update([
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_FINISHED
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Evaluation is finished');

        $this->action->reset($snapshot, $user);
    }

    public function test_reset_fails_without_permission(): void
    {
        // Evaluator role does NOT have MANAGE_USER_FEEDBACK permission by default unless overridden
        // But let's check Permissions: resetSnapshot requires PERMISSION_MANAGE_USER_FEEDBACK
        
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        
        // Remove manage_user_feedback permission if present (admin usually has it)
        // Or create a user that is just a member without that permission if possible.
        // For simplicity, let's just use a second user in the same team without the permission.
        
        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'evaluator']); 
        $member->switchTeam($team);

        $endpoint = SearchEndpoint::factory()->create(['team_id' => $team->id]);
        $model = SearchModel::factory()->create(['team_id' => $team->id, 'endpoint_id' => $endpoint->id]);
        $evaluation = SearchEvaluation::factory()->active()->create(['model_id' => $model->id]);
        $keyword = EvaluationKeyword::factory()->create(['search_evaluation_id' => $evaluation->id]);
        $snapshot = SearchSnapshot::factory()->create(['evaluation_keyword_id' => $keyword->id]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->action->reset($snapshot, $member);
    }
}
