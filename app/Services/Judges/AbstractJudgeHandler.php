<?php

namespace App\Services\Judges;

use App\Models\Judge;
use GuzzleHttp\ClientInterface;

abstract class AbstractJudgeHandler
{
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
}
