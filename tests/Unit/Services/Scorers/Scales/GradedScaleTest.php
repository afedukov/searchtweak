<?php

namespace Tests\Unit\Services\Scorers\Scales;

use App\Services\Scorers\Scales\GradedScale;
use PHPUnit\Framework\TestCase;

class GradedScaleTest extends TestCase
{
    private GradedScale $scale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scale = new GradedScale();
    }

    public function test_type(): void
    {
        $this->assertEquals('graded', $this->scale->getType());
    }

    public function test_name(): void
    {
        $this->assertEquals('Graded Scale', $this->scale->getName());
    }

    public function test_values(): void
    {
        $values = $this->scale->getValues();

        $this->assertCount(4, $values);
        $this->assertEquals('Poor', $values[GradedScale::POOR]);
        $this->assertEquals('Fair', $values[GradedScale::FAIR]);
        $this->assertEquals('Good', $values[GradedScale::GOOD]);
        $this->assertEquals('Perfect', $values[GradedScale::PERFECT]);
    }

    public function test_shortcuts(): void
    {
        $shortcuts = $this->scale->getShortcuts();

        $this->assertCount(4, $shortcuts);
        $this->assertEquals('1', $shortcuts[GradedScale::POOR]);
        $this->assertEquals('4', $shortcuts[GradedScale::PERFECT]);
    }

    public function test_get_value_average(): void
    {
        // (0 + 1 + 2 + 3) / 4 = 1.5
        $result = $this->scale->getValue([0, 1, 2, 3]);

        $this->assertEquals(1.5, $result);
    }

    public function test_get_value_all_perfect(): void
    {
        $result = $this->scale->getValue([3, 3, 3]);

        $this->assertEquals(3.0, $result);
    }

    public function test_get_value_empty_returns_null(): void
    {
        $result = $this->scale->getValue([]);

        $this->assertNull($result);
    }

    public function test_get_value_single(): void
    {
        $result = $this->scale->getValue([2]);

        $this->assertEquals(2.0, $result);
    }

    public function test_get_grades(): void
    {
        $grades = $this->scale->getGrades();

        $this->assertEquals([0, 1, 2, 3], $grades);
    }
}
