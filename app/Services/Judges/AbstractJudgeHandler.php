<?php

namespace App\Services\Judges;

use App\Models\Judge;
use App\Models\JudgeLog;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

abstract class AbstractJudgeHandler
{
    private ?int $evaluationId = null;
    private ?int $batchSize = null;
    private ?string $scaleType = null;

    public function __construct(protected readonly ClientInterface $client)
    {
    }

    /**
     * Send pairs to the LLM and return an array of grading results.
     *
     * @param Judge $judge
     * @param string $prompt Full prompt with #pairs# already replaced
     * @param array<int> $validGrades Valid grade integers for the scale
     *
     * @return array<int, array{pair_index: int, grade: int, reason: string}>
     *
     * @throws \RuntimeException On API failure, invalid JSON, or unexpected format
     */
    abstract public function grade(Judge $judge, string $prompt, array $validGrades): array;

    /**
     * Set context data used for logging each LLM call.
     */
    public function withContext(int $evaluationId, int $batchSize, string $scaleType): static
    {
        $this->evaluationId = $evaluationId;
        $this->batchSize = $batchSize;
        $this->scaleType = $scaleType;

        return $this;
    }

    /**
     * Build the prompt string by substituting the pairs JSON into the template.
     */
    public function buildPrompt(string $template, array $pairs): string
    {
        return str_replace(
            '#pairs#',
            json_encode($pairs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            $template,
        );
    }

    /**
     * Execute an HTTP request, log it, and return the extracted content string.
     *
     * @param Judge $judge
     * @param string $method HTTP method
     * @param string $url Full URL (may contain ?key=... which will be masked in log)
     * @param array $guzzleOptions Guzzle request options (JSON body, headers, timeouts)
     * @param callable $extractContent fn (array $decodedBody): string — extracts the text content from the decoded response
     *
     * @return string Extracted LLM content
     *
     * @throws \RuntimeException|\GuzzleHttp\Exception\GuzzleException On failure
     */
    protected function executeRequest(
        Judge $judge,
        string $method,
        string $url,
        array $guzzleOptions,
        callable $extractContent,
    ): string {
        $startedAt = microtime(true);
        $httpStatusCode = null;
        $responseRaw = null;
        $errorMessage = null;

        try {
            $response = $this->client->request($method, $url, $guzzleOptions);
            $httpStatusCode = $response->getStatusCode();
            $responseRaw = $response->getBody()->getContents();
            $decoded = json_decode($responseRaw, true) ?? [];

            return $extractContent($decoded);
        } catch (BadResponseException $e) {
            $httpStatusCode = $e->getResponse()->getStatusCode();
            $responseRaw = $e->getResponse()->getBody()->getContents();
            // Build a concise message without the response body (it is stored in response_body)
            // and with the URL sanitized to avoid leaking API keys.
            $errorMessage = sprintf(
                'HTTP %d %s: %s %s',
                $httpStatusCode,
                $e->getResponse()->getReasonPhrase(),
                strtoupper($method),
                $this->sanitizeUrl($url),
            );
            throw $e;
        } catch (GuzzleException $e) {
            // Sanitize any ?key=... in the URL that Guzzle embeds in its exception message.
            $errorMessage = preg_replace('/([?&]key=)[^&\s`\'"]+/', '$1***', $e->getMessage());
            throw $e;
        } finally {
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
            $decoded = ($responseRaw !== null) ? (json_decode($responseRaw, true) ?? []) : [];
            $tokens = $this->extractTokenUsage($judge->provider, $decoded);

            JudgeLog::create([
                JudgeLog::FIELD_JUDGE_ID => $judge->id,
                JudgeLog::FIELD_TEAM_ID => $judge->team_id,
                JudgeLog::FIELD_SEARCH_EVALUATION_ID => $this->evaluationId,
                JudgeLog::FIELD_PROVIDER => $judge->provider,
                JudgeLog::FIELD_MODEL => $judge->model_name,
                JudgeLog::FIELD_HTTP_STATUS_CODE => $httpStatusCode,
                JudgeLog::FIELD_REQUEST_URL => $this->sanitizeUrl($url),
                JudgeLog::FIELD_REQUEST_BODY => $this->sanitizeRequestBody($guzzleOptions),
                JudgeLog::FIELD_RESPONSE_BODY => $responseRaw,
                JudgeLog::FIELD_ERROR_MESSAGE => $errorMessage,
                JudgeLog::FIELD_LATENCY_MS => $latencyMs,
                JudgeLog::FIELD_PROMPT_TOKENS => $tokens['prompt_tokens'],
                JudgeLog::FIELD_COMPLETION_TOKENS => $tokens['completion_tokens'],
                JudgeLog::FIELD_TOTAL_TOKENS => $tokens['total_tokens'],
                JudgeLog::FIELD_BATCH_SIZE => $this->batchSize,
                JudgeLog::FIELD_SCALE_TYPE => $this->scaleType,
            ]);
        }
    }

    /**
     * Parse and validate the raw JSON response from the LLM.
     *
     * @param string $rawContent Raw LLM response text
     * @param array<int> $validGrades Valid grade values for the evaluation scale
     *
     * @return array<int, array{pair_index: int, grade: int, reason: string}>
     *
     * @throws \RuntimeException When JSON is invalid or structure is wrong
     */
    protected function parseResponse(string $rawContent, array $validGrades): array
    {
        // Strip markdown code fences if present (common LLM behavior)
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($rawContent));

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('LLM returned invalid JSON: ' . substr($rawContent, 0, 500));
        }

        $results = [];
        foreach ($decoded as $item) {
            if (!isset($item['pair_index'], $item['grade'])) {
                throw new \RuntimeException('LLM response item missing required fields (pair_index, grade)');
            }

            $grade = (int) $item['grade'];
            if (!in_array($grade, $validGrades, true)) {
                throw new \RuntimeException(sprintf(
                    'LLM returned invalid grade %d, valid grades: [%s]',
                    $grade,
                    implode(', ', $validGrades),
                ));
            }

            $results[(int) $item['pair_index']] = [
                'pair_index' => (int) $item['pair_index'],
                'grade' => $grade,
                'reason' => (string) ($item['reason'] ?? ''),
            ];
        }

        return $results;
    }

    /**
     * Mask the API key query parameter in the URL (e.g. ?key=VALUE → ?key=***).
     */
    private function sanitizeUrl(string $url): string
    {
        return preg_replace('/([?&]key=)[^&]+/', '$1***', $url);
    }

    /**
     * Extract and JSON-encode the request body from Guzzle options.
     * The API key never appears in the JSON body for any provider.
     */
    private function sanitizeRequestBody(array $guzzleOptions): string
    {
        $body = $guzzleOptions[RequestOptions::JSON] ?? [];

        return json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}';
    }

    /**
     * Extract token usage counts from the decoded response body by provider.
     *
     * @return array{prompt_tokens: int|null, completion_tokens: int|null, total_tokens: int|null}
     */
    private function extractTokenUsage(string $provider, array $body): array
    {
        return match ($provider) {
            Judge::PROVIDER_OPENAI => [
                'prompt_tokens' => $body['usage']['prompt_tokens'] ?? null,
                'completion_tokens' => $body['usage']['completion_tokens'] ?? null,
                'total_tokens' => $body['usage']['total_tokens'] ?? null,
            ],
            Judge::PROVIDER_ANTHROPIC => [
                'prompt_tokens' => $body['usage']['input_tokens'] ?? null,
                'completion_tokens' => $body['usage']['output_tokens'] ?? null,
                'total_tokens' => isset($body['usage']['input_tokens'], $body['usage']['output_tokens'])
                    ? $body['usage']['input_tokens'] + $body['usage']['output_tokens']
                    : null,
            ],
            Judge::PROVIDER_GOOGLE => [
                'prompt_tokens' => $body['usageMetadata']['promptTokenCount'] ?? null,
                'completion_tokens' => $body['usageMetadata']['candidatesTokenCount'] ?? null,
                'total_tokens' => $body['usageMetadata']['totalTokenCount'] ?? null,
            ],
            default => [
                'prompt_tokens' => null,
                'completion_tokens' => null,
                'total_tokens' => null,
            ],
        };
    }
}
