<?php

namespace Tests\Unit\Services\Scorers\Scales;

use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Scorers\Scales\Scale;
use App\Services\Scorers\Scales\ScaleFactory;
use PHPUnit\Framework\TestCase;

class ScaleFactoryTest extends TestCase
{
    public function test_creates_binary_scale(): void
    {
        $scale = ScaleFactory::create('binary');

        $this->assertInstanceOf(BinaryScale::class, $scale);
    }

    public function test_creates_graded_scale(): void
    {
        $scale = ScaleFactory::create('graded');

        $this->assertInstanceOf(GradedScale::class, $scale);
    }

    public function test_creates_detail_scale(): void
    {
        $scale = ScaleFactory::create('detail');

        $this->assertInstanceOf(DetailScale::class, $scale);
    }

    public function test_throws_on_invalid_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ScaleFactory::create('unknown');
    }

    public function test_get_scales_returns_all_three(): void
    {
        $scales = ScaleFactory::getScales();

        $this->assertCount(3, $scales);

        foreach ($scales as $scale) {
            $this->assertInstanceOf(Scale::class, $scale);
        }
    }
}
