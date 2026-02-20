<?php

namespace Tests\Feature\Services\Judges;

use App\Models\Judge;
use App\Models\JudgeLog;
use App\Models\User;
use App\Services\Judges\Providers\OpenAiCompatibleJudgeHandler;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenAiCompatibleJudgeHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_expected_request_for_default_openai_compatible_providers_and_logs_usage(): void
    {
        $cases = [
            [Judge::PROVIDER_DEEPSEEK, 'https://api.deepseek.com/v1/chat/completions', true],
            [Judge::PROVIDER_XAI, 'https://api.x.ai/v1/chat/completions', true],
            [Judge::PROVIDER_GROQ, 'https://api.groq.com/openai/v1/chat/completions', true],
            [Judge::PROVIDER_MISTRAL, 'https://api.mistral.ai/v1/chat/completions', true],
            [Judge::PROVIDER_OLLAMA, 'http://localhost:11434/v1/chat/completions', false],
        ];

        foreach ($cases as [$provider, $expectedUrl, $expectsAuthHeader]) {
            $judge = $this->createJudge($provider);
            if ($provider === Judge::PROVIDER_OLLAMA) {
                $judge->api_key = '';
            }

            $client = $this->createMock(ClientInterface::class);
            $client->expects($this->once())
                ->method('request')
                ->with(
                    'POST',
                    $expectedUrl,
                    $this->callback(function (array $options) use ($judge, $expectsAuthHeader) {
                        $headers = $options['headers'] ?? [];

                        $this->assertSame('application/json', $headers['Content-Type'] ?? null);

                        if ($expectsAuthHeader) {
                            $this->assertSame('Bearer ' . $judge->api_key, $headers['Authorization'] ?? null);
                        } else {
                            $this->assertArrayNotHasKey('Authorization', $headers);
                        }

                        $this->assertSame($judge->model_name, $options['json']['model'] ?? null);
                        $this->assertSame('user', $options['json']['messages'][0]['role'] ?? null);
                        $this->assertSame('Prompt text', $options['json']['messages'][0]['content'] ?? null);
                        $this->assertSame(0.1, $options['json']['temperature'] ?? null);

                        return true;
                    })
                )
                ->willReturn(new Response(200, [], json_encode([
                    'choices' => [[
                        'message' => [
                            'content' => json_encode([[
                                'pair_index' => 0,
                                'grade' => 3,
                                'reason' => 'Looks relevant',
                            ]], JSON_UNESCAPED_UNICODE),
                        ],
                    ]],
                    'usage' => [
                        'prompt_tokens' => 11,
                        'completion_tokens' => 7,
                        'total_tokens' => 18,
                    ],
                ], JSON_UNESCAPED_UNICODE)));

            $handler = new OpenAiCompatibleJudgeHandler($client);
            $results = $handler->grade($judge, 'Prompt text', [0, 1, 2, 3]);

            $this->assertSame(3, $results[0]['grade']);
            $this->assertSame('Looks relevant', $results[0]['reason']);

            $log = JudgeLog::query()->latest(JudgeLog::FIELD_ID)->firstOrFail();
            $this->assertSame($provider, $log->provider);
            $this->assertSame($expectedUrl, $log->request_url);
            $this->assertSame(11, $log->prompt_tokens);
            $this->assertSame(7, $log->completion_tokens);
            $this->assertSame(18, $log->total_tokens);

            JudgeLog::query()->delete();
        }
    }

    public function test_custom_provider_uses_base_url_and_appends_chat_completions(): void
    {
        $judge = $this->createJudge(Judge::PROVIDER_CUSTOM_OPENAI, [
            Judge::FIELD_SETTINGS => [
                Judge::SETTING_BASE_URL => 'https://example-llm.test/v1',
                Judge::SETTING_MODEL_PARAMS => ['temperature' => 0.2],
            ],
        ]);

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://example-llm.test/v1/chat/completions',
                $this->callback(function (array $options) use ($judge) {
                    $this->assertSame('Bearer ' . $judge->api_key, $options['headers']['Authorization'] ?? null);
                    $this->assertSame(0.2, $options['json']['temperature'] ?? null);

                    return true;
                })
            )
            ->willReturn(new Response(200, [], json_encode([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([[
                            'pair_index' => 0,
                            'grade' => 1,
                            'reason' => 'Not relevant',
                        ]], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 5,
                    'completion_tokens' => 4,
                    'total_tokens' => 9,
                ],
            ], JSON_UNESCAPED_UNICODE)));

        $handler = new OpenAiCompatibleJudgeHandler($client);
        $results = $handler->grade($judge, 'Prompt text', [0, 1, 2, 3]);

        $this->assertSame(1, $results[0]['grade']);
        $this->assertSame('Not relevant', $results[0]['reason']);

        $log = JudgeLog::query()->latest(JudgeLog::FIELD_ID)->firstOrFail();
        $this->assertSame('https://example-llm.test/v1/chat/completions', $log->request_url);
        $this->assertSame(5, $log->prompt_tokens);
        $this->assertSame(4, $log->completion_tokens);
        $this->assertSame(9, $log->total_tokens);
    }

    public function test_custom_provider_throws_when_base_url_is_missing(): void
    {
        $judge = $this->createJudge(Judge::PROVIDER_CUSTOM_OPENAI, [
            Judge::FIELD_SETTINGS => [],
        ]);

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('request');

        $handler = new OpenAiCompatibleJudgeHandler($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Custom OpenAI-compatible provider requires Base URL');

        $handler->grade($judge, 'Prompt text', [0, 1, 2, 3]);
    }

    public function test_ollama_provider_uses_custom_base_url_when_provided(): void
    {
        $judge = $this->createJudge(Judge::PROVIDER_OLLAMA, [
            Judge::FIELD_API_KEY => '',
            Judge::FIELD_SETTINGS => [
                Judge::SETTING_BASE_URL => 'http://ollama:11434/v1',
                Judge::SETTING_MODEL_PARAMS => ['temperature' => 0.3],
            ],
        ]);

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://ollama:11434/v1/chat/completions',
                $this->callback(function (array $options) {
                    $this->assertArrayNotHasKey('Authorization', $options['headers'] ?? []);
                    $this->assertSame(0.3, $options['json']['temperature'] ?? null);

                    return true;
                })
            )
            ->willReturn(new Response(200, [], json_encode([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([[
                            'pair_index' => 0,
                            'grade' => 2,
                            'reason' => 'Partially relevant',
                        ]], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 3,
                    'completion_tokens' => 2,
                    'total_tokens' => 5,
                ],
            ], JSON_UNESCAPED_UNICODE)));

        $handler = new OpenAiCompatibleJudgeHandler($client);
        $results = $handler->grade($judge, 'Prompt text', [0, 1, 2, 3]);

        $this->assertSame(2, $results[0]['grade']);

        $log = JudgeLog::query()->latest(JudgeLog::FIELD_ID)->firstOrFail();
        $this->assertSame('http://ollama:11434/v1/chat/completions', $log->request_url);
    }

    public function test_ollama_provider_adds_v1_path_when_base_url_has_only_host(): void
    {
        $judge = $this->createJudge(Judge::PROVIDER_OLLAMA, [
            Judge::FIELD_API_KEY => '',
            Judge::FIELD_SETTINGS => [
                Judge::SETTING_BASE_URL => 'http://ollama:11434',
                Judge::SETTING_MODEL_PARAMS => ['temperature' => 0.3],
            ],
        ]);

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://ollama:11434/v1/chat/completions',
                $this->anything()
            )
            ->willReturn(new Response(200, [], json_encode([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([[
                            'pair_index' => 0,
                            'grade' => 2,
                            'reason' => 'Partially relevant',
                        ]], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 3,
                    'completion_tokens' => 2,
                    'total_tokens' => 5,
                ],
            ], JSON_UNESCAPED_UNICODE)));

        $handler = new OpenAiCompatibleJudgeHandler($client);
        $handler->grade($judge, 'Prompt text', [0, 1, 2, 3]);

        $log = JudgeLog::query()->latest(JudgeLog::FIELD_ID)->firstOrFail();
        $this->assertSame('http://ollama:11434/v1/chat/completions', $log->request_url);
    }

    private function createJudge(string $provider, array $overrides = []): Judge
    {
        $user = User::factory()->withPersonalTeam()->create();

        return Judge::factory()->create(array_replace([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $user->currentTeam->id,
            Judge::FIELD_PROVIDER => $provider,
            Judge::FIELD_MODEL_NAME => 'model-test',
            Judge::FIELD_API_KEY => 'test-api-key',
            Judge::FIELD_SETTINGS => [
                Judge::SETTING_MODEL_PARAMS => ['temperature' => 0.1],
            ],
        ], $overrides));
    }
}
