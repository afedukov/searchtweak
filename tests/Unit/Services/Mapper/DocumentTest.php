<?php

namespace Tests\Unit\Services\Mapper;

use App\Services\Mapper\Document;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function test_getters_and_setters(): void
    {
        $doc = new Document();
        $doc->setId('123')
            ->setName('Test Product')
            ->setImage('https://img.example.com/1.jpg')
            ->setPosition(1)
            ->setAttributes(['color' => 'red']);

        $this->assertEquals('123', $doc->getId());
        $this->assertEquals('Test Product', $doc->getName());
        $this->assertEquals('https://img.example.com/1.jpg', $doc->getImage());
        $this->assertEquals(1, $doc->getPosition());
        $this->assertEquals(['color' => 'red'], $doc->getAttributes());
    }

    public function test_defaults(): void
    {
        $doc = new Document();

        $this->assertEquals('', $doc->getId());
        $this->assertEquals('', $doc->getName());
        $this->assertNull($doc->getImage());
        $this->assertEquals(0, $doc->getPosition());
        $this->assertEmpty($doc->getAttributes());
    }

    public function test_fluent_chaining(): void
    {
        $doc = (new Document())
            ->setId('abc')
            ->setName('Item')
            ->setPosition(5);

        $this->assertInstanceOf(Document::class, $doc);
        $this->assertEquals('abc', $doc->getId());
        $this->assertEquals('Item', $doc->getName());
        $this->assertEquals(5, $doc->getPosition());
    }

    public function test_set_attribute_adds_to_attributes(): void
    {
        $doc = new Document();
        $doc->setAttribute('color', 'blue');
        $doc->setAttribute('size', 'large');

        $this->assertEquals(['color' => 'blue', 'size' => 'large'], $doc->getAttributes());
    }

    public function test_to_array(): void
    {
        $doc = (new Document())
            ->setId('42')
            ->setName('Widget')
            ->setImage('https://img.test/w.png')
            ->setPosition(3)
            ->setAttributes(['brand' => 'Acme']);

        $expected = [
            'id' => '42',
            'name' => 'Widget',
            'image' => 'https://img.test/w.png',
            'position' => 3,
            'attributes' => ['brand' => 'Acme'],
        ];

        $this->assertEquals($expected, $doc->toArray());
    }

    public function test_create_from_array(): void
    {
        $data = [
            'id' => '99',
            'name' => 'From Array',
            'image' => 'https://img.test/99.png',
            'position' => 7,
            'attributes' => ['cat' => 'electronics'],
        ];

        $doc = Document::createFromArray($data);

        $this->assertEquals('99', $doc->getId());
        $this->assertEquals('From Array', $doc->getName());
        $this->assertEquals('https://img.test/99.png', $doc->getImage());
        $this->assertEquals(7, $doc->getPosition());
        $this->assertEquals(['cat' => 'electronics'], $doc->getAttributes());
    }

    public function test_create_from_array_without_optional_fields(): void
    {
        $data = [
            'id' => '1',
            'name' => 'Minimal',
            'position' => 1,
        ];

        $doc = Document::createFromArray($data);

        $this->assertEquals('1', $doc->getId());
        $this->assertEquals('Minimal', $doc->getName());
        $this->assertNull($doc->getImage());
        $this->assertEmpty($doc->getAttributes());
    }

    public function test_to_array_roundtrip(): void
    {
        $data = [
            'id' => '55',
            'name' => 'Roundtrip',
            'image' => null,
            'position' => 2,
            'attributes' => ['key' => 'val'],
        ];

        $doc = Document::createFromArray($data);
        $this->assertEquals($data, $doc->toArray());
    }
}
