<?php

namespace Tests\Unit\Models;

use App\Models\Judge;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Scorers\Scales\GradedScale;
use Tests\TestCase;

class JudgeTest extends TestCase
{
    public function test_get_default_prompt_returns_binary_template(): void
    {
        $prompt = Judge::getDefaultPrompt('binary');

        $this->assertStringContainsString('Binary', $prompt);
        $this->assertStringContainsString('#pairs#', $prompt);
        $this->assertStringContainsString('pair_index', $prompt);
    }

    public function test_get_default_prompt_returns_graded_template(): void
    {
        $prompt = Judge::getDefaultPrompt('graded');

        $this->assertStringContainsString('Graded', $prompt);
        $this->assertStringContainsString('#pairs#', $prompt);
        $this->assertStringContainsString('Poor', $prompt);
        $this->assertStringContainsString('Perfect', $prompt);
    }

    public function test_get_default_prompt_returns_detail_template(): void
    {
        $prompt = Judge::getDefaultPrompt('detail');

        $this->assertStringContainsString('Detail', $prompt);
        $this->assertStringContainsString('#pairs#', $prompt);
        $this->assertStringContainsString('1 to 10', $prompt);
    }

    public function test_prompts_constant_maps_scale_types_to_fields(): void
    {
        $this->assertEquals(Judge::FIELD_PROMPT_BINARY, Judge::PROMPTS[BinaryScale::SCALE_TYPE]);
        $this->assertEquals(Judge::FIELD_PROMPT_GRADED, Judge::PROMPTS[GradedScale::SCALE_TYPE]);
        $this->assertEquals(Judge::FIELD_PROMPT_DETAIL, Judge::PROMPTS[DetailScale::SCALE_TYPE]);
    }

    public function test_get_batch_size_returns_default_when_empty(): void
    {
        $judge = new Judge();
        $judge->settings = [];

        $this->assertEquals(Judge::DEFAULT_BATCH_SIZE, $judge->getBatchSize());
    }

    public function test_get_batch_size_returns_default_when_settings_null(): void
    {
        $judge = new Judge();
        $judge->settings = null;

        $this->assertEquals(Judge::DEFAULT_BATCH_SIZE, $judge->getBatchSize());
    }

    public function test_get_batch_size_returns_stored_value(): void
    {
        $judge = new Judge();
        $judge->settings = [Judge::SETTING_BATCH_SIZE => 12];

        $this->assertEquals(12, $judge->getBatchSize());
    }

    public function test_get_base_url_returns_null_for_missing_or_empty_value(): void
    {
        $judge = new Judge();
        $judge->settings = [];
        $this->assertNull($judge->getBaseUrl());

        $judge->settings = [Judge::SETTING_BASE_URL => '   '];
        $this->assertNull($judge->getBaseUrl());
    }

    public function test_get_base_url_returns_trimmed_value(): void
    {
        $judge = new Judge();
        $judge->settings = [Judge::SETTING_BASE_URL => ' https://example.com/v1/ '];

        $this->assertSame('https://example.com/v1/', $judge->getBaseUrl());
    }

    public function test_provider_requires_api_key_returns_false_for_ollama(): void
    {
        $this->assertFalse(Judge::providerRequiresApiKey(Judge::PROVIDER_OLLAMA));
        $this->assertTrue(Judge::providerRequiresApiKey(Judge::PROVIDER_OPENAI));
    }

    public function test_provider_labels_include_new_providers(): void
    {
        $this->assertSame('DeepSeek', Judge::getProviderLabel(Judge::PROVIDER_DEEPSEEK));
        $this->assertSame('xAI', Judge::getProviderLabel(Judge::PROVIDER_XAI));
        $this->assertSame('Groq', Judge::getProviderLabel(Judge::PROVIDER_GROQ));
        $this->assertSame('Mistral', Judge::getProviderLabel(Judge::PROVIDER_MISTRAL));
        $this->assertSame('Custom (OpenAI-compatible)', Judge::getProviderLabel(Judge::PROVIDER_CUSTOM_OPENAI));
        $this->assertSame('Ollama', Judge::getProviderLabel(Judge::PROVIDER_OLLAMA));
    }
}
