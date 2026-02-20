<?php

namespace App\Services\Judges\Providers;

use App\Models\Judge;
use App\Services\Judges\AbstractJudgeHandler;
use GuzzleHttp\RequestOptions;

class OpenAiCompatibleJudgeHandler extends AbstractJudgeHandler
{
    private const int TIMEOUT = 120;

    private const array API_URLS = [
        Judge::PROVIDER_DEEPSEEK => 'https://api.deepseek.com/v1/chat/completions',
        Judge::PROVIDER_XAI => 'https://api.x.ai/v1/chat/completions',
        Judge::PROVIDER_GROQ => 'https://api.groq.com/openai/v1/chat/completions',
        Judge::PROVIDER_MISTRAL => 'https://api.mistral.ai/v1/chat/completions',
        Judge::PROVIDER_OLLAMA => 'http://localhost:11434/v1/chat/completions',
    ];

    public function grade(Judge $judge, string $prompt, array $validGrades): array
    {
        $content = $this->executeRequest(
            judge: $judge,
            method: 'POST',
            url: $this->resolveApiUrl($judge),
            guzzleOptions: [
                RequestOptions::TIMEOUT => self::TIMEOUT,
                RequestOptions::CONNECT_TIMEOUT => 10,
                RequestOptions::HEADERS => $this->buildHeaders($judge),
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

    private function resolveApiUrl(Judge $judge): string
    {
        if ($judge->provider === Judge::PROVIDER_CUSTOM_OPENAI) {
            $baseUrl = $judge->getBaseUrl();
            if ($baseUrl === null) {
                throw new \RuntimeException('Custom OpenAI-compatible provider requires Base URL.');
            }

            return $this->normalizeApiUrl($baseUrl);
        }

        if ($judge->provider === Judge::PROVIDER_OLLAMA) {
            return $this->normalizeOllamaApiUrl($judge->getBaseUrl() ?? self::API_URLS[Judge::PROVIDER_OLLAMA]);
        }

        $apiUrl = self::API_URLS[$judge->provider] ?? null;
        if ($apiUrl === null) {
            throw new \RuntimeException(sprintf('Provider "%s" is not OpenAI-compatible.', $judge->provider));
        }

        return $apiUrl;
    }

    private function normalizeApiUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');

        return str_ends_with($url, '/chat/completions')
            ? $url
            : $url . '/chat/completions';
    }

    private function normalizeOllamaApiUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');

        if (str_ends_with($url, '/v1/chat/completions')) {
            return $url;
        }

        if (str_ends_with($url, '/chat/completions')) {
            $base = substr($url, 0, -strlen('/chat/completions'));

            return rtrim($base, '/') . '/v1/chat/completions';
        }

        if (str_ends_with($url, '/v1')) {
            return $url . '/chat/completions';
        }

        return $url . '/v1/chat/completions';
    }

    private function buildHeaders(Judge $judge): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if (is_string($judge->api_key) && $judge->api_key !== '' && Judge::providerRequiresApiKey($judge->provider)) {
            $headers['Authorization'] = 'Bearer ' . $judge->api_key;
        }

        return $headers;
    }
}
