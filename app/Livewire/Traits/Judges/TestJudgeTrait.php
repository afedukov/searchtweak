<?php

namespace App\Livewire\Traits\Judges;

use App\Models\Judge;
use App\Services\Judges\JudgeHandlerFactory;
use App\Services\Scorers\Scales\ScaleFactory;
use GuzzleHttp\Exception\GuzzleException;

trait TestJudgeTrait
{
    public ?array $judgeTestResult = null;

    public string $judgeTestScaleType = 'binary';

    private const array TEST_PAIRS = [
        [
            'pair_index' => 0,
            'query' => 'wireless headphones',
            'doc_id' => 'test-001',
            'name' => 'Sony WH-1000XM5 Wireless Noise Cancelling Headphones',
            'position' => 1,
            'doc' => ['price' => '349.99', 'brand' => 'Sony'],
        ],
        [
            'pair_index' => 1,
            'query' => 'wireless headphones',
            'doc_id' => 'test-002',
            'name' => 'Organic Green Tea Bags, 100 Count',
            'position' => 2,
            'doc' => ['price' => '12.99', 'brand' => 'TeaTime'],
        ],
    ];

    public function testJudge(JudgeHandlerFactory $handlerFactory): void
    {
        $this->judgeTestResult = null;

        $scaleType = $this->judgeTestScaleType;
        $promptField = 'prompt_' . $scaleType;
        $prompt = $this->judgeForm->{$promptField};

        if (empty($this->judgeForm->provider) || empty($this->judgeForm->model_name)) {
            $this->judgeTestResult = [
                'successful' => false,
                'error' => 'Provider and Model Name are required to test.',
            ];

            return;
        }

        // Resolve API key: use form value or fall back to saved key when editing
        $apiKey = $this->judgeForm->api_key;
        if (empty($apiKey) && $this->judgeForm->judge !== null) {
            $apiKey = $this->judgeForm->judge->api_key;
        }

        if (empty($apiKey)) {
            $this->judgeTestResult = [
                'successful' => false,
                'error' => 'API Key is required to test.',
            ];

            return;
        }

        if (empty($prompt)) {
            $this->judgeTestResult = [
                'successful' => false,
                'error' => sprintf('Prompt for "%s" scale is empty.', $scaleType),
            ];

            return;
        }

        // Build a temporary Judge model (not saved) with form values
        $tempJudge = new Judge();
        $tempJudge->provider = $this->judgeForm->provider;
        $tempJudge->model_name = $this->judgeForm->model_name;
        $tempJudge->api_key = $apiKey;

        try {
            $handler = $handlerFactory->create($tempJudge);
            $validGrades = ScaleFactory::create($scaleType)->getGrades();
            $builtPrompt = $handler->buildPrompt($prompt, self::TEST_PAIRS);
            $results = $handler->grade($tempJudge, $builtPrompt, $validGrades);

            $this->judgeTestResult = [
                'successful' => true,
                'response' => json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'graded_count' => count($results),
                'pairs_count' => count(self::TEST_PAIRS),
                'grades' => array_values(array_map(fn (array $r) => [
                    'pair_index' => $r['pair_index'],
                    'grade' => $r['grade'],
                    'reason' => $r['reason'],
                    'product' => self::TEST_PAIRS[$r['pair_index']]['name'] ?? 'Unknown',
                ], $results)),
            ];
        } catch (GuzzleException|\RuntimeException $e) {
            $this->judgeTestResult = [
                'successful' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
