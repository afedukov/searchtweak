<?php

namespace Tests\Unit\Services\Scorers\Scales;

use App\Services\Scorers\Scales\DetailScale;
use PHPUnit\Framework\TestCase;

class DetailScaleTest extends TestCase
{
    private DetailScale $scale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scale = new DetailScale();
    }

    public function test_type(): void
    {
        $this->assertEquals('detail', $this->scale->getType());
    }

    public function test_name(): void
    {
        $this->assertEquals('Detail Scale', $this->scale->getName());
    }

    public function test_has_ten_values(): void
    {
        $values = $this->scale->getValues();

        $this->assertCount(10, $values);
        $this->assertEquals('1', $values[DetailScale::V_1]);
        $this->assertEquals('10', $values[DetailScale::V_10]);
    }

    public function test_shortcuts(): void
    {
        $shortcuts = $this->scale->getShortcuts();

        $this->assertCount(10, $shortcuts);
        $this->assertEquals('1', $shortcuts[DetailScale::V_1]);
        $this->assertEquals('0', $shortcuts[DetailScale::V_10]);
    }

    public function test_get_value_average(): void
    {
        // (1 + 5 + 10) / 3 ≈ 5.333
        $result = $this->scale->getValue([1, 5, 10]);

        $this->assertEqualsWithDelta(5.333, $result, 0.001);
    }

    public function test_get_value_all_max(): void
    {
        $result = $this->scale->getValue([10, 10, 10]);

        $this->assertEquals(10.0, $result);
    }

    public function test_get_value_empty_returns_null(): void
    {
        $result = $this->scale->getValue([]);

        $this->assertNull($result);
    }

    public function test_get_value_single(): void
    {
        $result = $this->scale->getValue([7]);

        $this->assertEquals(7.0, $result);
    }

    public function test_get_grades(): void
    {
        $grades = $this->scale->getGrades();

        $this->assertEquals(range(1, 10), $grades);
    }
}
