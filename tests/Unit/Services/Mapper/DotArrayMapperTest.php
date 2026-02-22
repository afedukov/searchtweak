<?php

namespace Tests\Unit\Services\Mapper;

use App\Services\Mapper\DotArrayMapper;
use Tests\TestCase;

class DotArrayMapperTest extends TestCase
{
    public function test_initialize_parses_mapper_code(): void
    {
        $mapper = new DotArrayMapper("id: data.items.*.id\nname: data.items.*.title");
        $mapper->initialize();

        $attributes = $mapper->getAttributes();

        $this->assertContains('id', $attributes);
        $this->assertContains('name', $attributes);
    }

    public function test_validate_passes_with_required_attributes(): void
    {
        $mapper = new DotArrayMapper("id: data.items.*.id\nname: data.items.*.title");
        $mapper->initialize();

        $this->assertTrue($mapper->validate());
        $this->assertEmpty($mapper->getError());
    }

    public function test_validate_fails_without_id(): void
    {
        $mapper = new DotArrayMapper("name: data.items.*.title");
        $mapper->initialize();

        $this->assertFalse($mapper->validate());
        $this->assertStringContainsString('id', $mapper->getError());
    }

    public function test_validate_fails_without_name(): void
    {
        $mapper = new DotArrayMapper("id: data.items.*.id");
        $mapper->initialize();

        $this->assertFalse($mapper->validate());
        $this->assertStringContainsString('name', $mapper->getError());
    }

    public function test_get_documents_from_json(): void
    {
        $mapperCode = "id: data.items.*.id\nname: data.items.*.title";
        $mapper = new DotArrayMapper($mapperCode);
        $mapper->initialize();

        $json = json_encode([
            'items' => [
                ['id' => '1', 'title' => 'First'],
                ['id' => '2', 'title' => 'Second'],
                ['id' => '3', 'title' => 'Third'],
            ],
        ]);

        $docs = $mapper->getDocuments($json, 10);

        $this->assertCount(3, $docs);
        $this->assertEquals('1', $docs[0]->getId());
        $this->assertEquals('First', $docs[0]->getName());
        $this->assertEquals(1, $docs[0]->getPosition());
        $this->assertEquals('3', $docs[2]->getId());
        $this->assertEquals(3, $docs[2]->getPosition());
    }

    public function test_get_documents_respects_limit(): void
    {
        $mapperCode = "id: data.items.*.id\nname: data.items.*.title";
        $mapper = new DotArrayMapper($mapperCode);
        $mapper->initialize();

        $items = array_map(fn ($i) => ['id' => (string) $i, 'title' => "Item $i"], range(1, 20));
        $json = json_encode(['items' => $items]);

        $docs = $mapper->getDocuments($json, 5);

        $this->assertCount(5, $docs);
    }

    public function test_get_documents_with_image_attribute(): void
    {
        $mapperCode = "id: data.products.*.sku\nname: data.products.*.name\nimage: data.products.*.img";
        $mapper = new DotArrayMapper($mapperCode);
        $mapper->initialize();

        $json = json_encode([
            'products' => [
                ['sku' => 'A1', 'name' => 'Widget', 'img' => 'https://img.test/w.png'],
            ],
        ]);

        $docs = $mapper->getDocuments($json, 10);

        $this->assertCount(1, $docs);
        $this->assertEquals('https://img.test/w.png', $docs[0]->getImage());
    }

    public function test_get_documents_invalid_json_returns_empty(): void
    {
        $mapper = new DotArrayMapper("id: data.items.*.id\nname: data.items.*.title");
        $mapper->initialize();

        $docs = $mapper->getDocuments('not valid json', 10);

        $this->assertCount(0, $docs);
    }

    public function test_get_documents_removes_invalid_documents(): void
    {
        $mapperCode = "id: data.items.*.id\nname: data.items.*.title";
        $mapper = new DotArrayMapper($mapperCode);
        $mapper->initialize();

        $json = json_encode([
            'items' => [
                ['id' => '1', 'title' => 'Valid'],
                ['id' => '', 'title' => 'Missing ID'],
                ['id' => '3', 'title' => ''],
            ],
        ]);

        $docs = $mapper->getDocuments($json, 10);

        $this->assertCount(1, $docs);
        $this->assertEquals('1', $docs[0]->getId());
    }

    public function test_get_documents_with_custom_attributes(): void
    {
        $mapperCode = "id: data.items.*.id\nname: data.items.*.title\nprice: data.items.*.price";
        $mapper = new DotArrayMapper($mapperCode);
        $mapper->initialize();

        $json = json_encode([
            'items' => [
                ['id' => '1', 'title' => 'Product', 'price' => '9.99'],
            ],
        ]);

        $docs = $mapper->getDocuments($json, 10);

        $this->assertCount(1, $docs);
        $this->assertEquals(['price' => '9.99'], $docs[0]->getAttributes());
    }

    public function test_initialize_skips_empty_lines(): void
    {
        $mapper = new DotArrayMapper("id: data.items.*.id\n\nname: data.items.*.title\n");
        $mapper->initialize();

        $this->assertTrue($mapper->validate());
    }

    public function test_get_documents_with_array_wildcard_attribute_returns_array_values(): void
    {
        $mapperCode = "id: data.items.*.id\nname: data.items.*.title\ntags: data.items.*.tags.*";
        $mapper = new DotArrayMapper($mapperCode);
        $mapper->initialize();

        $json = json_encode([
            'items' => [
                ['id' => '1', 'title' => 'Product 1', 'tags' => ['a', 'b']],
                ['id' => '2', 'title' => 'Product 2', 'tags' => ['c']],
            ],
        ]);

        $docs = $mapper->getDocuments($json, 10);

        $this->assertCount(2, $docs);
        $this->assertEquals(['tags' => ['a', 'b']], $docs[0]->getAttributes());
        $this->assertEquals(['tags' => ['c']], $docs[1]->getAttributes());
    }

    public function test_get_documents_ignores_invalid_expression_and_keeps_required_fields(): void
    {
        $mapperCode = "id: data.items.*.id\nname: data.items.*.title\nbroken: data.items.*.price +";
        $mapper = new DotArrayMapper($mapperCode);
        $mapper->initialize();

        $json = json_encode([
            'items' => [
                ['id' => '1', 'title' => 'First', 'price' => '9.99'],
            ],
        ]);

        $docs = $mapper->getDocuments($json, 10);

        $this->assertCount(1, $docs);
        $this->assertEquals('1', $docs[0]->getId());
        $this->assertEquals('First', $docs[0]->getName());
        $this->assertArrayNotHasKey('broken', $docs[0]->getAttributes());
    }

    public function test_get_documents_filters_out_invalid_id_and_name_types(): void
    {
        $mapperCode = "id: data.items.*.id\nname: data.items.*.title";
        $mapper = new DotArrayMapper($mapperCode);
        $mapper->initialize();

        $json = json_encode([
            'items' => [
                ['id' => ['100'], 'title' => 'Array id'], // invalid id type
                ['id' => '2', 'title' => ['200']], // invalid name type
                ['id' => '3', 'title' => 'Valid'],
            ],
        ]);

        $docs = $mapper->getDocuments($json, 10);

        $this->assertCount(1, $docs);
        $this->assertEquals('3', $docs[0]->getId());
        $this->assertEquals('Valid', $docs[0]->getName());
    }

    public function test_get_documents_with_scalar_json_returns_empty(): void
    {
        $mapper = new DotArrayMapper("id: data.items.*.id\nname: data.items.*.title");
        $mapper->initialize();

        $docs = $mapper->getDocuments('123', 10);

        $this->assertCount(0, $docs);
    }

    public function test_get_documents_isolated_between_calls(): void
    {
        $mapper = new DotArrayMapper("id: data.items.*.id\nname: data.items.*.title");
        $mapper->initialize();

        $firstJson = json_encode([
            'items' => [
                ['id' => '1', 'title' => 'First'],
                ['id' => '2', 'title' => 'Second'],
            ],
        ]);

        $secondJson = json_encode([
            'items' => [
                ['id' => '10', 'title' => 'Only one'],
            ],
        ]);

        $firstDocs = $mapper->getDocuments($firstJson, 10);
        $secondDocs = $mapper->getDocuments($secondJson, 10);

        $this->assertCount(2, $firstDocs);
        $this->assertCount(1, $secondDocs);
        $this->assertEquals('10', $secondDocs[0]->getId());
        $this->assertEquals(1, $secondDocs[0]->getPosition());
    }

    public function test_get_documents_with_expression_using_multiple_data_terms(): void
    {
        $mapperCode = "id: data.items.*.id\nname: data.items.*.brand ~ ' ' ~ data.items.*.title";
        $mapper = new DotArrayMapper($mapperCode);
        $mapper->initialize();

        $json = json_encode([
            'items' => [
                ['id' => '1', 'brand' => 'Acme', 'title' => 'Fridge'],
            ],
        ]);

        $docs = $mapper->getDocuments($json, 10);

        $this->assertCount(1, $docs);
        $this->assertEquals('Acme Fridge', $docs[0]->getName());
    }
}
