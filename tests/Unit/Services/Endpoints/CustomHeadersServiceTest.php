<?php

namespace Tests\Unit\Services\Endpoints;

use App\Services\Endpoints\CustomHeadersService;
use PHPUnit\Framework\TestCase;

class CustomHeadersServiceTest extends TestCase
{
    private CustomHeadersService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CustomHeadersService();
    }

    public function test_compose_headers_from_valid_string(): void
    {
        $source = "Authorization: Bearer token123\nContent-Type: application/json";

        $result = $this->service->composeHeadersArray($source);

        $this->assertEquals([
            'Authorization' => 'Bearer token123',
            'Content-Type' => 'application/json',
        ], $result);
    }

    public function test_compose_headers_trims_whitespace(): void
    {
        $source = "  X-Api-Key  :  abc123  ";

        $result = $this->service->composeHeadersArray($source);

        $this->assertEquals(['X-Api-Key' => 'abc123'], $result);
    }

    public function test_compose_headers_skips_invalid_lines(): void
    {
        $source = "Valid-Header: value\nno-colon-here\n: missing-key\nmissing-value:\n";

        $result = $this->service->composeHeadersArray($source);

        $this->assertEquals(['Valid-Header' => 'value'], $result);
    }

    public function test_compose_headers_empty_input(): void
    {
        $result = $this->service->composeHeadersArray('');

        $this->assertEmpty($result);
    }

    public function test_compose_headers_deduplicates_lines(): void
    {
        $source = "X-Key: val1\nX-Key: val1";

        $result = $this->service->composeHeadersArray($source);

        $this->assertEquals(['X-Key' => 'val1'], $result);
    }

    public function test_decompose_headers_array(): void
    {
        $headers = [
            'Authorization' => 'Bearer token',
            'Accept' => 'application/json',
        ];

        $result = $this->service->decomposeHeadersArray($headers);

        $this->assertEquals("Authorization: Bearer token\nAccept: application/json", $result);
    }

    public function test_decompose_empty_array(): void
    {
        $result = $this->service->decomposeHeadersArray([]);

        $this->assertEquals('', $result);
    }

    public function test_roundtrip_compose_decompose(): void
    {
        $original = ['X-Key' => 'value1', 'Y-Key' => 'value2'];

        $decomposed = $this->service->decomposeHeadersArray($original);
        $composed = $this->service->composeHeadersArray($decomposed);

        $this->assertEquals($original, $composed);
    }
}
