<?php

namespace Tests\Feature\Livewire;

use App\Livewire\JudgeLogs;
use App\Models\Judge;
use App\Models\JudgeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JudgeLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_judge_logs_route_is_accessible(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/judges/logs')
            ->assertOk();
    }

    public function test_per_judge_logs_route_is_accessible_for_own_team_judge(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        $this->actingAs($user)
            ->get("/judges/{$judge->id}/logs")
            ->assertOk();
    }

    public function test_per_judge_logs_route_is_forbidden_for_other_team_judge(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $other = User::factory()->withPersonalTeam()->create();
        $foreignJudge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $other->id,
            Judge::FIELD_TEAM_ID => $other->currentTeam->id,
        ]);

        $this->actingAs($user)
            ->get("/judges/{$foreignJudge->id}/logs")
            ->assertForbidden();
    }

    public function test_per_judge_mode_scopes_logs_to_selected_judge(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);
        $otherJudge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_NAME => 'Other Judge',
        ]);

        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => $judge->id,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_PROVIDER => $judge->provider,
            JudgeLog::FIELD_MODEL => $judge->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => 200,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/selected',
            JudgeLog::FIELD_REQUEST_BODY => '{}',
            JudgeLog::FIELD_RESPONSE_BODY => '{}',
            JudgeLog::FIELD_ERROR_MESSAGE => null,
        ]);

        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => $otherJudge->id,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_PROVIDER => $otherJudge->provider,
            JudgeLog::FIELD_MODEL => $otherJudge->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => 200,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/other',
            JudgeLog::FIELD_REQUEST_BODY => '{}',
            JudgeLog::FIELD_RESPONSE_BODY => '{}',
            JudgeLog::FIELD_ERROR_MESSAGE => null,
        ]);

        Livewire::actingAs($user)
            ->test(JudgeLogs::class, ['judge' => $judge])
            ->assertSet('filterJudgeId', $judge->id)
            ->assertDontSee('Other Judge')
            ->assertViewHas('logs', function ($logs): bool {
                return $logs->total() === 1;
            });
    }

    public function test_global_mode_includes_logs_of_deleted_judges_scoped_by_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => null,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_PROVIDER => 'openai',
            JudgeLog::FIELD_MODEL => 'gpt-5',
            JudgeLog::FIELD_HTTP_STATUS_CODE => 500,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/error',
            JudgeLog::FIELD_REQUEST_BODY => '{}',
            JudgeLog::FIELD_RESPONSE_BODY => '{}',
            JudgeLog::FIELD_ERROR_MESSAGE => 'HTTP 500',
        ]);

        Livewire::actingAs($user)
            ->test(JudgeLogs::class)
            ->assertSee('Judge deleted')
            ->assertSee('openai/gpt-5');
    }

    public function test_status_filter_success_and_error_work(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => null,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_PROVIDER => 'openai',
            JudgeLog::FIELD_MODEL => 'gpt-5',
            JudgeLog::FIELD_HTTP_STATUS_CODE => 200,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/success',
            JudgeLog::FIELD_REQUEST_BODY => '{}',
            JudgeLog::FIELD_RESPONSE_BODY => '{}',
            JudgeLog::FIELD_ERROR_MESSAGE => null,
        ]);

        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => null,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_PROVIDER => 'openai',
            JudgeLog::FIELD_MODEL => 'gpt-5',
            JudgeLog::FIELD_HTTP_STATUS_CODE => 500,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/error',
            JudgeLog::FIELD_REQUEST_BODY => '{}',
            JudgeLog::FIELD_RESPONSE_BODY => '{}',
            JudgeLog::FIELD_ERROR_MESSAGE => 'HTTP 500',
        ]);

        Livewire::actingAs($user)
            ->test(JudgeLogs::class)
            ->set('filterStatus', 'success')
            ->assertViewHas('logs', function ($logs): bool {
                return $logs->total() === 1
                    && (int) $logs->items()[0]->http_status_code === 200;
            });

        Livewire::actingAs($user)
            ->test(JudgeLogs::class)
            ->set('filterStatus', 'error')
            ->assertViewHas('logs', function ($logs): bool {
                return $logs->total() === 1
                    && (int) $logs->items()[0]->http_status_code === 500;
            });
    }
}
