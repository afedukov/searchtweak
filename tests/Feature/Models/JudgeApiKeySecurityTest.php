<?php

namespace Tests\Feature\Models;

use App\Livewire\Judges;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class JudgeApiKeySecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_is_encrypted_at_rest_in_database(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $plainApiKey = 'sk-live-security-check-123';

        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_API_KEY => $plainApiKey,
        ]);

        $rawStoredValue = DB::table('judges')
            ->where(Judge::FIELD_ID, $judge->id)
            ->value(Judge::FIELD_API_KEY);

        $this->assertIsString($rawStoredValue);
        $this->assertNotSame($plainApiKey, $rawStoredValue);
        $this->assertNotEmpty($rawStoredValue);

        $judge->refresh();
        $this->assertSame($plainApiKey, $judge->api_key);
    }

    public function test_api_key_is_hidden_from_serialized_model_payloads(): void
    {
        $judge = Judge::factory()->create([
            Judge::FIELD_API_KEY => 'sk-live-serialized-check-456',
        ]);

        $this->assertArrayNotHasKey(Judge::FIELD_API_KEY, $judge->toArray());
        $this->assertStringNotContainsString(Judge::FIELD_API_KEY, $judge->toJson());
        $this->assertStringNotContainsString('sk-live-serialized-check-456', $judge->toJson());
    }

    public function test_edit_judge_form_never_prefills_existing_api_key(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $plainApiKey = 'sk-live-form-check-789';

        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_API_KEY => $plainApiKey,
        ]);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('editJudge', $judge)
            ->assertSet('judgeForm.api_key', '')
            ->assertDontSee($plainApiKey);
    }
}
