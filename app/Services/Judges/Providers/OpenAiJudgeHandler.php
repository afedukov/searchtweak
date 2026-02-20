<?php

namespace App\Services\Judges\Providers;

use App\Models\Judge;
use App\Services\Judges\AbstractJudgeHandler;
use GuzzleHttp\RequestOptions;

class OpenAiJudgeHandler extends AbstractJudgeHandler
{
    private const string API_URL = 'https://api.openai.com/v1/chat/completions';
    private const int TIMEOUT = 120;

    public function grade(Judge $judge, string $prompt, array $validGrades): array
    {
        $content = $this->executeRequest(
            judge: $judge,
            method: 'POST',
            url: self::API_URL,
            guzzleOptions: [
                RequestOptions::TIMEOUT => self::TIMEOUT,
                RequestOptions::CONNECT_TIMEOUT => 10,
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $judge->api_key,
                    'Content-Type' => 'application/json',
                ],
                RequestOptions::JSON => array_merge([
                    'model' => $judge->model_name,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ], $judge->getModelParams()),
            ],
            extractContent: fn (array $body) => $body['choices'][0]['message']['content'] ?? '',
        );

        return $this->parseResponse($content, $validGrades);
    }
}
