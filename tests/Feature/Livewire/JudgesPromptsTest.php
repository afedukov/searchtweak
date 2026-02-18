<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Judges;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JudgesPromptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_judge_loads_default_prompts(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $component = Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('createJudge');

        $component
            ->assertSet('judgeForm.prompt_binary', Judge::getDefaultPrompt('binary'))
            ->assertSet('judgeForm.prompt_graded', Judge::getDefaultPrompt('graded'))
            ->assertSet('judgeForm.prompt_detail', Judge::getDefaultPrompt('detail'));
    }

    public function test_create_judge_saves_all_three_prompts(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('createJudge')
            ->set('judgeForm.name', 'Prompt Test Judge')
            ->set('judgeForm.provider', Judge::PROVIDER_OPENAI)
            ->set('judgeForm.model_name', 'gpt-4')
            ->set('judgeForm.api_key', 'sk-test-key')
            ->set('judgeForm.prompt_binary', 'Binary prompt content')
            ->set('judgeForm.prompt_graded', 'Graded prompt content')
            ->set('judgeForm.prompt_detail', 'Detail prompt content')
            ->call('saveJudge');

        $judge = Judge::where(Judge::FIELD_NAME, 'Prompt Test Judge')->firstOrFail();

        $this->assertEquals('Binary prompt content', $judge->prompt_binary);
        $this->assertEquals('Graded prompt content', $judge->prompt_graded);
        $this->assertEquals('Detail prompt content', $judge->prompt_detail);
    }

    public function test_create_judge_saves_batch_size_in_settings(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('createJudge')
            ->set('judgeForm.name', 'Batch Size Judge')
            ->set('judgeForm.provider', Judge::PROVIDER_OPENAI)
            ->set('judgeForm.model_name', 'gpt-4')
            ->set('judgeForm.api_key', 'sk-test-key')
            ->set('judgeForm.setting_batch_size', 10)
            ->call('saveJudge');

        $judge = Judge::where(Judge::FIELD_NAME, 'Batch Size Judge')->firstOrFail();

        $this->assertEquals(10, $judge->getBatchSize());
        $this->assertEquals(10, $judge->settings[Judge::SETTING_BATCH_SIZE]);
    }

    public function test_edit_judge_loads_all_three_prompts(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_PROMPT_BINARY => 'Saved binary prompt',
            Judge::FIELD_PROMPT_GRADED => 'Saved graded prompt',
            Judge::FIELD_PROMPT_DETAIL => 'Saved detail prompt',
        ]);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('editJudge', $judge)
            ->assertSet('judgeForm.prompt_binary', 'Saved binary prompt')
            ->assertSet('judgeForm.prompt_graded', 'Saved graded prompt')
            ->assertSet('judgeForm.prompt_detail', 'Saved detail prompt');
    }

    public function test_edit_judge_loads_batch_size_from_settings(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_SETTINGS => [Judge::SETTING_BATCH_SIZE => 5],
        ]);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('editJudge', $judge)
            ->assertSet('judgeForm.setting_batch_size', 5);
    }

    public function test_edit_judge_defaults_batch_size_when_settings_empty(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_SETTINGS => [],
        ]);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('editJudge', $judge)
            ->assertSet('judgeForm.setting_batch_size', Judge::DEFAULT_BATCH_SIZE);
    }

    public function test_clone_judge_copies_all_three_prompts(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_PROMPT_BINARY => 'Clone binary',
            Judge::FIELD_PROMPT_GRADED => 'Clone graded',
            Judge::FIELD_PROMPT_DETAIL => 'Clone detail',
            Judge::FIELD_SETTINGS => [Judge::SETTING_BATCH_SIZE => 15],
        ]);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('cloneJudge', $judge)
            ->assertSet('judgeForm.prompt_binary', 'Clone binary')
            ->assertSet('judgeForm.prompt_graded', 'Clone graded')
            ->assertSet('judgeForm.prompt_detail', 'Clone detail')
            ->assertSet('judgeForm.setting_batch_size', 15)
            ->assertSet('judgeForm.judge', null);
    }

    public function test_update_judge_persists_changed_prompts(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_PROMPT_BINARY => 'Old binary',
            Judge::FIELD_PROMPT_GRADED => 'Old graded',
            Judge::FIELD_PROMPT_DETAIL => 'Old detail',
            Judge::FIELD_SETTINGS => [Judge::SETTING_BATCH_SIZE => 3],
        ]);

        Livewire::actingAs($user)
            ->test(Judges::class)
            ->call('editJudge', $judge)
            ->set('judgeForm.prompt_binary', 'New binary')
            ->set('judgeForm.prompt_graded', 'New graded')
            ->set('judgeForm.prompt_detail', 'New detail')
            ->set('judgeForm.setting_batch_size', 8)
            ->call('saveJudge');

        $judge->refresh();

        $this->assertEquals('New binary', $judge->prompt_binary);
        $this->assertEquals('New graded', $judge->prompt_graded);
        $this->assertEquals('New detail', $judge->prompt_detail);
        $this->assertEquals(8, $judge->getBatchSize());
    }
}
