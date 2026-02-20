<?php

namespace App\Services\Judges;

use App\Models\Judge;
use App\Services\Judges\Providers\AnthropicJudgeHandler;
use App\Services\Judges\Providers\GoogleJudgeHandler;
use App\Services\Judges\Providers\OpenAiJudgeHandler;
use App\Services\Judges\Providers\OpenAiCompatibleJudgeHandler;
use GuzzleHttp\ClientInterface;

class JudgeHandlerFactory
{
    public function __construct(private readonly ClientInterface $client)
    {
    }

    public function create(Judge $judge): AbstractJudgeHandler
    {
        return match ($judge->provider) {
            Judge::PROVIDER_OPENAI => new OpenAiJudgeHandler($this->client),
            Judge::PROVIDER_ANTHROPIC => new AnthropicJudgeHandler($this->client),
            Judge::PROVIDER_GOOGLE => new GoogleJudgeHandler($this->client),
            Judge::PROVIDER_DEEPSEEK,
            Judge::PROVIDER_XAI,
            Judge::PROVIDER_GROQ,
            Judge::PROVIDER_MISTRAL,
            Judge::PROVIDER_CUSTOM_OPENAI,
            Judge::PROVIDER_OLLAMA => new OpenAiCompatibleJudgeHandler($this->client),
            default => throw new \InvalidArgumentException(
                sprintf('Unknown judge provider: %s', $judge->provider),
            ),
        };
    }
}
