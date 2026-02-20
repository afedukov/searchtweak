<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Judges;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JudgeProvidersFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_provider_requires_base_url_and_api_key_on_create(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('createJudge')
            ->set('judgeForm.name', 'Custom Provider Judge')
            ->set('judgeForm.provider', Judge::PROVIDER_CUSTOM_OPENAI)
            ->set('judgeForm.model_name', 'custom-model')
            ->set('judgeForm.api_key', '')
            ->set('judgeForm.setting_base_url', '')
            ->call('saveJudge')
            ->assertHasErrors([
                'judgeForm.api_key' => 'required',
                'judgeForm.setting_base_url' => 'required',
            ]);
    }

    public function test_ollama_provider_allows_empty_api_key_and_saves_without_base_url(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('createJudge')
            ->set('judgeForm.name', 'Ollama Judge')
            ->set('judgeForm.provider', Judge::PROVIDER_OLLAMA)
            ->set('judgeForm.model_name', 'llama3.2')
            ->set('judgeForm.api_key', '')
            ->set('judgeForm.setting_base_url', '')
            ->call('saveJudge')
            ->assertHasNoErrors();

        $judge = Judge::query()
            ->where(Judge::FIELD_TEAM_ID, $user->currentTeam->id)
            ->where(Judge::FIELD_NAME, 'Ollama Judge')
            ->firstOrFail();

        $this->assertSame(Judge::PROVIDER_OLLAMA, $judge->provider);
        $this->assertNull($judge->getBaseUrl());
    }

    public function test_custom_provider_saves_base_url_in_settings(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('createJudge')
            ->set('judgeForm.name', 'Custom URL Judge')
            ->set('judgeForm.provider', Judge::PROVIDER_CUSTOM_OPENAI)
            ->set('judgeForm.model_name', 'custom-model')
            ->set('judgeForm.api_key', 'custom-api-key')
            ->set('judgeForm.setting_base_url', 'https://custom-llm.example/v1')
            ->call('saveJudge')
            ->assertHasNoErrors();

        $judge = Judge::query()
            ->where(Judge::FIELD_TEAM_ID, $user->currentTeam->id)
            ->where(Judge::FIELD_NAME, 'Custom URL Judge')
            ->firstOrFail();

        $this->assertSame('https://custom-llm.example/v1', $judge->getBaseUrl());
        $this->assertSame('custom-model', $judge->model_name);
    }

    public function test_editing_ollama_judge_to_provider_with_required_api_key_requires_key_when_stored_key_is_empty(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_NAME => 'Switch Provider Judge',
            Judge::FIELD_PROVIDER => Judge::PROVIDER_OLLAMA,
            Judge::FIELD_MODEL_NAME => 'llama3.2',
            Judge::FIELD_API_KEY => '',
            Judge::FIELD_SETTINGS => [],
        ]);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('editJudge', $judge)
            ->set('judgeForm.provider', Judge::PROVIDER_OPENAI)
            ->set('judgeForm.model_name', 'gpt-5')
            ->set('judgeForm.api_key', '')
            ->call('saveJudge')
            ->assertHasErrors([
                'judgeForm.api_key' => 'required',
            ]);
    }
}
