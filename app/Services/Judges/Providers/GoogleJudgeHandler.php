<?php

namespace App\Services\Judges\Providers;

use App\Models\Judge;
use App\Services\Judges\AbstractJudgeHandler;
use GuzzleHttp\RequestOptions;

class GoogleJudgeHandler extends AbstractJudgeHandler
{
    private const string API_URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const int TIMEOUT = 120;

    public function grade(Judge $judge, string $prompt, array $validGrades): array
    {
        $url = sprintf(self::API_URL_TEMPLATE, $judge->model_name);

        $response = $this->client->request('POST', $url, [
            RequestOptions::TIMEOUT => self::TIMEOUT,
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::QUERY => ['key' => $judge->api_key],
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
            ],
            RequestOptions::JSON => array_filter([
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => $judge->getModelParams() ?: null,
            ], fn($v) => $v !== null),
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return $this->parseResponse($content, $validGrades);
    }
}
