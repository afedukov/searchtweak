<?php

namespace Tests\Unit\Services\Judges;

use App\Models\Judge;
use App\Services\Judges\JudgeHandlerFactory;
use App\Services\Judges\Providers\AnthropicJudgeHandler;
use App\Services\Judges\Providers\GoogleJudgeHandler;
use App\Services\Judges\Providers\OpenAiCompatibleJudgeHandler;
use App\Services\Judges\Providers\OpenAiJudgeHandler;
use GuzzleHttp\ClientInterface;
use Tests\TestCase;

class JudgeHandlerFactoryTest extends TestCase
{
    public function test_it_creates_expected_handler_per_provider(): void
    {
        $factory = new JudgeHandlerFactory($this->createMock(ClientInterface::class));

        $openAiJudge = new Judge([Judge::FIELD_PROVIDER => Judge::PROVIDER_OPENAI]);
        $anthropicJudge = new Judge([Judge::FIELD_PROVIDER => Judge::PROVIDER_ANTHROPIC]);
        $googleJudge = new Judge([Judge::FIELD_PROVIDER => Judge::PROVIDER_GOOGLE]);
        $deepseekJudge = new Judge([Judge::FIELD_PROVIDER => Judge::PROVIDER_DEEPSEEK]);
        $xAiJudge = new Judge([Judge::FIELD_PROVIDER => Judge::PROVIDER_XAI]);
        $groqJudge = new Judge([Judge::FIELD_PROVIDER => Judge::PROVIDER_GROQ]);
        $mistralJudge = new Judge([Judge::FIELD_PROVIDER => Judge::PROVIDER_MISTRAL]);
        $customJudge = new Judge([Judge::FIELD_PROVIDER => Judge::PROVIDER_CUSTOM_OPENAI]);
        $ollamaJudge = new Judge([Judge::FIELD_PROVIDER => Judge::PROVIDER_OLLAMA]);

        $this->assertInstanceOf(OpenAiJudgeHandler::class, $factory->create($openAiJudge));
        $this->assertInstanceOf(AnthropicJudgeHandler::class, $factory->create($anthropicJudge));
        $this->assertInstanceOf(GoogleJudgeHandler::class, $factory->create($googleJudge));
        $this->assertInstanceOf(OpenAiCompatibleJudgeHandler::class, $factory->create($deepseekJudge));
        $this->assertInstanceOf(OpenAiCompatibleJudgeHandler::class, $factory->create($xAiJudge));
        $this->assertInstanceOf(OpenAiCompatibleJudgeHandler::class, $factory->create($groqJudge));
        $this->assertInstanceOf(OpenAiCompatibleJudgeHandler::class, $factory->create($mistralJudge));
        $this->assertInstanceOf(OpenAiCompatibleJudgeHandler::class, $factory->create($customJudge));
        $this->assertInstanceOf(OpenAiCompatibleJudgeHandler::class, $factory->create($ollamaJudge));
    }

    public function test_it_throws_for_unknown_provider(): void
    {
        $factory = new JudgeHandlerFactory($this->createMock(ClientInterface::class));
        $judge = new Judge([Judge::FIELD_PROVIDER => 'unknown-provider']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown judge provider');

        $factory->create($judge);
    }
}
