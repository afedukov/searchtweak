<?php

namespace Tests\Unit\Services\Judges;

use App\Services\Judges\JudgeParamsService;
use Tests\TestCase;

class JudgeParamsServiceTest extends TestCase
{
    public function test_compose_params_array_casts_supported_types_and_ignores_invalid_lines(): void
    {
        $service = new JudgeParamsService();

        $source = implode("\n", [
            'temperature: 0.1',
            'max_tokens: 2048',
            'stream: true',
            'top_p: 0.95',
            'metadata: null',
            'label: judge',
            'invalid-line-without-colon',
            ': missing-key',
        ]);

        $params = $service->composeParamsArray($source);

        $this->assertSame(0.1, $params['temperature']);
        $this->assertSame(2048, $params['max_tokens']);
        $this->assertTrue($params['stream']);
        $this->assertSame(0.95, $params['top_p']);
        $this->assertNull($params['metadata']);
        $this->assertSame('judge', $params['label']);
        $this->assertArrayNotHasKey('invalid-line-without-colon', $params);
    }

    public function test_compose_params_array_keeps_last_value_for_duplicate_keys(): void
    {
        $service = new JudgeParamsService();

        $source = implode("\n", [
            'temperature: 0.1',
            'temperature: 0.2',
        ]);

        $params = $service->composeParamsArray($source);

        $this->assertSame(0.2, $params['temperature']);
    }

    public function test_decompose_params_array_formats_scalar_values(): void
    {
        $service = new JudgeParamsService();

        $text = $service->decomposeParamsArray([
            'temperature' => 0.1,
            'max_tokens' => 1024,
            'stream' => false,
            'metadata' => null,
            'label' => 'judge',
        ]);

        $this->assertStringContainsString('temperature: 0.1', $text);
        $this->assertStringContainsString('max_tokens: 1024', $text);
        $this->assertStringContainsString('stream: false', $text);
        $this->assertStringContainsString('metadata: null', $text);
        $this->assertStringContainsString('label: judge', $text);
    }
}
