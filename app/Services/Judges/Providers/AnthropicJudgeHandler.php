<?php

namespace App\Services\Judges\Providers;

use App\Models\Judge;
use App\Services\Judges\AbstractJudgeHandler;
use GuzzleHttp\RequestOptions;

class AnthropicJudgeHandler extends AbstractJudgeHandler
{
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
    private const string API_VERSION = '2023-06-01';
    private const int TIMEOUT = 120;
    private const int MAX_TOKENS = 4096;

    public function grade(Judge $judge, string $prompt, array $validGrades): array
    {
        $response = $this->client->request('POST', self::API_URL, [
            RequestOptions::TIMEOUT => self::TIMEOUT,
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::HEADERS => [
                'x-api-key' => $judge->api_key,
                'anthropic-version' => self::API_VERSION,
                'Content-Type' => 'application/json',
            ],
            RequestOptions::JSON => array_merge([
                'model' => $judge->model_name,
                'max_tokens' => self::MAX_TOKENS,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ], $judge->getModelParams()),
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $content = $body['content'][0]['text'] ?? '';

        return $this->parseResponse($content, $validGrades);
    }
}
