<?php

namespace Tests\Unit\Services\Scorers\Scales;

use App\Services\Scorers\Scales\BinaryScale;
use PHPUnit\Framework\TestCase;

class BinaryScaleTest extends TestCase
{
    private BinaryScale $scale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scale = new BinaryScale();
    }

    public function test_type(): void
    {
        $this->assertEquals('binary', $this->scale->getType());
    }

    public function test_name(): void
    {
        $this->assertEquals('Binary Scale', $this->scale->getName());
    }

    public function test_values(): void
    {
        $values = $this->scale->getValues();

        $this->assertCount(2, $values);
        $this->assertEquals('Irrelevant', $values[BinaryScale::IRRELEVANT]);
        $this->assertEquals('Relevant', $values[BinaryScale::RELEVANT]);
    }

    public function test_shortcuts(): void
    {
        $shortcuts = $this->scale->getShortcuts();

        $this->assertEquals('1', $shortcuts[BinaryScale::IRRELEVANT]);
        $this->assertEquals('2', $shortcuts[BinaryScale::RELEVANT]);
    }

    public function test_get_value_majority_relevant(): void
    {
        $result = $this->scale->getValue([1, 1, 0]);

        $this->assertEquals(BinaryScale::RELEVANT, $result);
    }

    public function test_get_value_majority_irrelevant(): void
    {
        $result = $this->scale->getValue([0, 0, 1]);

        $this->assertEquals(BinaryScale::IRRELEVANT, $result);
    }

    public function test_get_value_tie_returns_null(): void
    {
        $result = $this->scale->getValue([0, 1]);

        $this->assertNull($result);
    }

    public function test_get_value_empty_returns_null(): void
    {
        $result = $this->scale->getValue([]);

        $this->assertNull($result);
    }

    public function test_get_value_single_relevant(): void
    {
        $result = $this->scale->getValue([1]);

        $this->assertEquals(BinaryScale::RELEVANT, $result);
    }

    public function test_get_value_single_irrelevant(): void
    {
        $result = $this->scale->getValue([0]);

        $this->assertEquals(BinaryScale::IRRELEVANT, $result);
    }

    public function test_get_grades(): void
    {
        $grades = $this->scale->getGrades();

        $this->assertEquals([0, 1], $grades);
    }

    public function test_json_serialize(): void
    {
        $json = $this->scale->jsonSerialize();

        $this->assertEquals('binary', $json['type']);
        $this->assertEquals('Binary Scale', $json['name']);
        $this->assertArrayHasKey('values', $json);
    }
}
