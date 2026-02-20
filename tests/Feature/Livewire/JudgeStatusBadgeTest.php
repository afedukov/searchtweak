<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Judges\JudgeStatusBadge;
use App\Models\EvaluationKeyword;
use App\Models\Judge;
use App\Models\JudgeLog;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JudgeStatusBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_judge_without_claimed_ungraded_feedback_is_waiting(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_ARCHIVED_AT => null,
        ]);

        Livewire::actingAs($user)
            ->test(JudgeStatusBadge::class, [
                'judgeId' => $judge->id,
                'teamId' => $user->currentTeam->id,
            ])
            ->assertSee('Waiting')
            ->assertDontSee('Working');
    }

    public function test_active_judge_with_claimed_ungraded_feedback_is_working(): void
    {
        [$user, $snapshot] = $this->createSnapshotForTeam();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_ARCHIVED_AT => null,
        ]);

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        Livewire::actingAs($user)
            ->test(JudgeStatusBadge::class, [
                'judgeId' => $judge->id,
                'teamId' => $user->currentTeam->id,
            ])
            ->assertSee('Working')
            ->assertDontSee('Waiting');
    }

    public function test_archived_judge_has_no_status_badge(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->archived()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        Livewire::actingAs($user)
            ->test(JudgeStatusBadge::class, [
                'judgeId' => $judge->id,
                'teamId' => $user->currentTeam->id,
            ])
            ->assertDontSee('Working')
            ->assertDontSee('Waiting')
            ->assertDontSee('Error');
    }

    public function test_shows_error_badge_when_last_log_failed_and_hides_when_last_is_successful(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_ARCHIVED_AT => null,
        ]);

        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => $judge->id,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_PROVIDER => $judge->provider,
            JudgeLog::FIELD_MODEL => $judge->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => 500,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/v1/chat/completions',
            JudgeLog::FIELD_REQUEST_BODY => '{}',
            JudgeLog::FIELD_RESPONSE_BODY => '{}',
            JudgeLog::FIELD_ERROR_MESSAGE => 'HTTP 500',
        ]);

        Livewire::actingAs($user)
            ->test(JudgeStatusBadge::class, [
                'judgeId' => $judge->id,
                'teamId' => $user->currentTeam->id,
            ])
            ->assertSee('Error');

        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => $judge->id,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_PROVIDER => $judge->provider,
            JudgeLog::FIELD_MODEL => $judge->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => 200,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/v1/chat/completions',
            JudgeLog::FIELD_REQUEST_BODY => '{}',
            JudgeLog::FIELD_RESPONSE_BODY => '{}',
            JudgeLog::FIELD_ERROR_MESSAGE => null,
        ]);

        Livewire::actingAs($user)
            ->test(JudgeStatusBadge::class, [
                'judgeId' => $judge->id,
                'teamId' => $user->currentTeam->id,
            ])
            ->assertDontSee('Error');
    }

    private function createSnapshotForTeam(): array
    {
        $user = User::factory()->withPersonalTeam()->create();

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
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $snapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
        ]);

        return [$user, $snapshot];
    }
}
