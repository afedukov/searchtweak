<?php

namespace Tests\Unit\Services\Models;

use App\Models\SearchModel;
use App\Services\Models\RequestHeadersService;
use PHPUnit\Framework\TestCase;

class RequestHeadersServiceTest extends TestCase
{
    public static function contentTypeProvider(): array
    {
        return [
            'JSON' => [SearchModel::BODY_TYPE_JSON, 'application/json'],
            'Text' => [SearchModel::BODY_TYPE_TEXT, 'text/plain'],
            'XML' => [SearchModel::BODY_TYPE_XML, 'application/xml'],
            'HTML' => [SearchModel::BODY_TYPE_HTML, 'text/html'],
            'JavaScript' => [SearchModel::BODY_TYPE_JAVASCRIPT, 'application/javascript'],
            'Form' => [SearchModel::BODY_TYPE_FORM, 'application/x-www-form-urlencoded'],
        ];
    }

    /**
     * @dataProvider contentTypeProvider
     */
    public function test_get_content_type_header(int $bodyType, string $expectedContentType): void
    {
        $header = RequestHeadersService::getContentTypeHeader($bodyType);

        $this->assertEquals(['Content-Type' => $expectedContentType], $header);
    }

    public function test_get_content_type_header_unknown_defaults_to_json(): void
    {
        $header = RequestHeadersService::getContentTypeHeader(999);

        $this->assertEquals(['Content-Type' => 'application/json'], $header);
    }

    public function test_get_body_types_returns_all_six(): void
    {
        $types = RequestHeadersService::getBodyTypes();

        $this->assertCount(6, $types);
        $this->assertEquals('JSON', $types[SearchModel::BODY_TYPE_JSON]);
        $this->assertEquals('Text', $types[SearchModel::BODY_TYPE_TEXT]);
        $this->assertEquals('XML', $types[SearchModel::BODY_TYPE_XML]);
        $this->assertEquals('HTML', $types[SearchModel::BODY_TYPE_HTML]);
        $this->assertEquals('JavaScript', $types[SearchModel::BODY_TYPE_JAVASCRIPT]);
        $this->assertEquals('Form', $types[SearchModel::BODY_TYPE_FORM]);
    }
}
