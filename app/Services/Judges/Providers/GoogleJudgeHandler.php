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
        // Append the API key to the URL so sanitizeUrl() can mask it,
        // instead of passing it via RequestOptions::QUERY which would not be masked.
        $url = sprintf(self::API_URL_TEMPLATE, $judge->model_name) . '?key=' . urlencode($judge->api_key);

        $content = $this->executeRequest(
            judge: $judge,
            method: 'POST',
            url: $url,
            guzzleOptions: [
                RequestOptions::TIMEOUT => self::TIMEOUT,
                RequestOptions::CONNECT_TIMEOUT => 10,
                RequestOptions::HEADERS => [
                    'Content-Type' => 'application/json',
                ],
                RequestOptions::JSON => array_filter([
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => $judge->getModelParams() ?: null,
                ], fn ($v) => $v !== null),
            ],
            extractContent: fn (array $body) => $body['candidates'][0]['content']['parts'][0]['text'] ?? '',
        );

        return $this->parseResponse($content, $validGrades);
    }
}
