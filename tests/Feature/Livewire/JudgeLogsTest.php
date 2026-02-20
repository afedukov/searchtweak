<?php

namespace Tests\Feature\Livewire;

use App\Livewire\JudgeLogs;
use App\Models\Judge;
use App\Models\JudgeLog;
use App\Models\SearchEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    public function test_export_jsonl_respects_current_filters_and_includes_full_payload(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $judgeA = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_NAME => 'Judge A',
        ]);
        $judgeB = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_NAME => 'Judge B',
        ]);

        $evaluationA = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_NAME => 'Evaluation A',
        ]);
        $evaluationB = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_NAME => 'Evaluation B',
        ]);

        $matching = JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => $judgeA->id,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_SEARCH_EVALUATION_ID => $evaluationA->id,
            JudgeLog::FIELD_PROVIDER => $judgeA->provider,
            JudgeLog::FIELD_MODEL => $judgeA->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => 500,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/a',
            JudgeLog::FIELD_REQUEST_BODY => '{"prompt":"A"}',
            JudgeLog::FIELD_RESPONSE_BODY => '{"error":"A"}',
            JudgeLog::FIELD_ERROR_MESSAGE => 'HTTP 500',
            JudgeLog::FIELD_PROMPT_TOKENS => 11,
            JudgeLog::FIELD_COMPLETION_TOKENS => 22,
            JudgeLog::FIELD_TOTAL_TOKENS => 33,
            JudgeLog::FIELD_BATCH_SIZE => 2,
            JudgeLog::FIELD_SCALE_TYPE => 'graded',
        ]);

        // Different status.
        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => $judgeA->id,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_SEARCH_EVALUATION_ID => $evaluationA->id,
            JudgeLog::FIELD_PROVIDER => $judgeA->provider,
            JudgeLog::FIELD_MODEL => $judgeA->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => 200,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/success',
            JudgeLog::FIELD_REQUEST_BODY => '{"prompt":"ok"}',
            JudgeLog::FIELD_RESPONSE_BODY => '{"ok":true}',
            JudgeLog::FIELD_ERROR_MESSAGE => null,
        ]);

        // Different judge.
        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => $judgeB->id,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_SEARCH_EVALUATION_ID => $evaluationA->id,
            JudgeLog::FIELD_PROVIDER => $judgeB->provider,
            JudgeLog::FIELD_MODEL => $judgeB->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => 500,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/b',
            JudgeLog::FIELD_REQUEST_BODY => '{"prompt":"B"}',
            JudgeLog::FIELD_RESPONSE_BODY => '{"error":"B"}',
            JudgeLog::FIELD_ERROR_MESSAGE => 'HTTP 500',
        ]);

        // Different evaluation.
        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => $judgeA->id,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_SEARCH_EVALUATION_ID => $evaluationB->id,
            JudgeLog::FIELD_PROVIDER => $judgeA->provider,
            JudgeLog::FIELD_MODEL => $judgeA->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => 500,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/eval-b',
            JudgeLog::FIELD_REQUEST_BODY => '{"prompt":"eval-b"}',
            JudgeLog::FIELD_RESPONSE_BODY => '{"error":"eval-b"}',
            JudgeLog::FIELD_ERROR_MESSAGE => 'HTTP 500',
        ]);

        $this->actingAs($user);

        $component = app(JudgeLogs::class);
        $component->filterStatus = 'error';
        $component->filterJudgeId = $judgeA->id;
        $component->filterEvaluationId = $evaluationA->id;

        $response = $component->exportJsonl();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString('application/x-ndjson', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('judge-logs_', (string) $response->headers->get('content-disposition'));

        ob_start();
        $response->sendContent();
        $content = trim((string) ob_get_clean());

        $lines = array_values(array_filter(explode(PHP_EOL, $content)));
        $this->assertCount(1, $lines);

        $row = json_decode($lines[0], true);

        $this->assertSame($matching->id, $row[JudgeLog::FIELD_ID]);
        $this->assertSame($judgeA->id, $row[JudgeLog::FIELD_JUDGE_ID]);
        $this->assertSame('Judge A', $row['judge_name']);
        $this->assertSame($evaluationA->id, $row[JudgeLog::FIELD_SEARCH_EVALUATION_ID]);
        $this->assertSame('Evaluation A', $row['evaluation_name']);
        $this->assertSame('error', $row['status']);
        $this->assertSame('{"prompt":"A"}', $row[JudgeLog::FIELD_REQUEST_BODY]);
        $this->assertSame('{"error":"A"}', $row[JudgeLog::FIELD_RESPONSE_BODY]);
        $this->assertSame('HTTP 500', $row[JudgeLog::FIELD_ERROR_MESSAGE]);
        $this->assertSame(11, $row[JudgeLog::FIELD_PROMPT_TOKENS]);
        $this->assertSame(22, $row[JudgeLog::FIELD_COMPLETION_TOKENS]);
        $this->assertSame(33, $row[JudgeLog::FIELD_TOTAL_TOKENS]);
        $this->assertArrayHasKey(JudgeLog::FIELD_CREATED_AT, $row);
        $this->assertArrayHasKey(JudgeLog::FIELD_UPDATED_AT, $row);
    }

    public function test_export_jsonl_in_per_judge_mode_is_hard_scoped_to_selected_judge(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $judgeA = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_NAME => 'Scoped Judge',
        ]);
        $judgeB = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_NAME => 'Other Judge',
        ]);

        $logA = JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => $judgeA->id,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_PROVIDER => $judgeA->provider,
            JudgeLog::FIELD_MODEL => $judgeA->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => 200,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/scoped',
            JudgeLog::FIELD_REQUEST_BODY => '{"prompt":"scoped"}',
            JudgeLog::FIELD_RESPONSE_BODY => '{"ok":true}',
            JudgeLog::FIELD_ERROR_MESSAGE => null,
        ]);

        JudgeLog::create([
            JudgeLog::FIELD_JUDGE_ID => $judgeB->id,
            JudgeLog::FIELD_TEAM_ID => $user->currentTeam->id,
            JudgeLog::FIELD_PROVIDER => $judgeB->provider,
            JudgeLog::FIELD_MODEL => $judgeB->model_name,
            JudgeLog::FIELD_HTTP_STATUS_CODE => 200,
            JudgeLog::FIELD_REQUEST_URL => 'https://example.test/other',
            JudgeLog::FIELD_REQUEST_BODY => '{"prompt":"other"}',
            JudgeLog::FIELD_RESPONSE_BODY => '{"ok":true}',
            JudgeLog::FIELD_ERROR_MESSAGE => null,
        ]);

        $this->actingAs($user);

        $component = app(JudgeLogs::class);
        $component->mount((string) $judgeA->id);
        $component->filterJudgeId = $judgeB->id; // Should be ignored in per-judge mode.

        $response = $component->exportJsonl();

        ob_start();
        $response->sendContent();
        $content = trim((string) ob_get_clean());

        $lines = array_values(array_filter(explode(PHP_EOL, $content)));
        $this->assertCount(1, $lines);

        $row = json_decode($lines[0], true);
        $this->assertSame($logA->id, $row[JudgeLog::FIELD_ID]);
        $this->assertSame($judgeA->id, $row[JudgeLog::FIELD_JUDGE_ID]);
        $this->assertSame('Scoped Judge', $row['judge_name']);
    }
}
