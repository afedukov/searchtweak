<?php

namespace Tests\Unit\Services\Mapper;

use App\Models\SearchEndpoint;
use App\Services\Mapper\DotArrayMapper;
use App\Services\Mapper\MapperFactory;
use PHPUnit\Framework\TestCase;

class MapperFactoryTest extends TestCase
{
    private MapperFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new MapperFactory();
    }

    public function test_creates_dot_array_mapper(): void
    {
        $mapper = $this->factory->create(SearchEndpoint::MAPPER_TYPE_DOT_ARRAY, 'id: data.id');

        $this->assertInstanceOf(DotArrayMapper::class, $mapper);
    }

    public function test_throws_on_unknown_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->factory->create(999, 'test');
    }
}
