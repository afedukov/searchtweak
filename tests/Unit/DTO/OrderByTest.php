<?php

namespace Tests\Unit\DTO;

use App\DTO\OrderBy;
use PHPUnit\Framework\TestCase;

class OrderByTest extends TestCase
{
    public function test_default_values(): void
    {
        $orderBy = new OrderBy();

        $this->assertEquals(OrderBy::ORDER_BY_DEFAULT, $orderBy->getMetricId());
        $this->assertEquals('asc', $orderBy->getDirection());
        $this->assertTrue($orderBy->isDefault());
        $this->assertTrue($orderBy->isDefaultBy());
        $this->assertTrue($orderBy->isDefaultDirection());
    }

    public function test_custom_values(): void
    {
        $orderBy = new OrderBy(5, 'desc');

        $this->assertEquals(5, $orderBy->getMetricId());
        $this->assertEquals('desc', $orderBy->getDirection());
        $this->assertFalse($orderBy->isDefault());
        $this->assertFalse($orderBy->isDefaultBy());
        $this->assertFalse($orderBy->isDefaultDirection());
    }

    public function test_throws_on_invalid_direction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid direction');

        new OrderBy(0, 'invalid');
    }

    public function test_is_default_requires_both_conditions(): void
    {
        // Default metric but non-default direction
        $orderBy = new OrderBy(OrderBy::ORDER_BY_DEFAULT, 'desc');
        $this->assertTrue($orderBy->isDefaultBy());
        $this->assertFalse($orderBy->isDefaultDirection());
        $this->assertFalse($orderBy->isDefault());
    }

    public function test_order_by_keyword_constant(): void
    {
        $orderBy = new OrderBy(OrderBy::ORDER_BY_KEYWORD, 'asc');

        $this->assertEquals(OrderBy::ORDER_BY_KEYWORD, $orderBy->getMetricId());
        $this->assertFalse($orderBy->isDefaultBy());
    }

    public function test_set_metric_id_returns_self(): void
    {
        $orderBy = new OrderBy();
        $result = $orderBy->setMetricId(10);

        $this->assertSame($orderBy, $result);
        $this->assertEquals(10, $orderBy->getMetricId());
    }

    public function test_set_direction_returns_self(): void
    {
        $orderBy = new OrderBy();
        $result = $orderBy->setDirection('desc');

        $this->assertSame($orderBy, $result);
        $this->assertEquals('desc', $orderBy->getDirection());
    }

    public function test_to_livewire(): void
    {
        $orderBy = new OrderBy(7, 'desc');
        $data = $orderBy->toLivewire();

        $this->assertEquals([
            'metricId' => 7,
            'direction' => 'desc',
        ], $data);
    }

    public function test_from_livewire(): void
    {
        $data = ['metricId' => 3, 'direction' => 'asc'];
        $orderBy = OrderBy::fromLivewire($data);

        $this->assertInstanceOf(OrderBy::class, $orderBy);
        $this->assertEquals(3, $orderBy->getMetricId());
        $this->assertEquals('asc', $orderBy->getDirection());
    }

    public function test_to_livewire_from_livewire_roundtrip(): void
    {
        $original = new OrderBy(42, 'desc');
        $restored = OrderBy::fromLivewire($original->toLivewire());

        $this->assertEquals($original->getMetricId(), $restored->getMetricId());
        $this->assertEquals($original->getDirection(), $restored->getDirection());
    }
}
