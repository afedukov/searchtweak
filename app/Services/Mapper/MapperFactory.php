<?php

namespace App\Services\Mapper;

use App\Models\SearchEndpoint;

class MapperFactory
{
    public function create(int $mapperType, string $mapperCode): MapperInterface
    {
        return match ($mapperType) {
            SearchEndpoint::MAPPER_TYPE_DOT_ARRAY => new DotArrayMapper($mapperCode),
            default => throw new \InvalidArgumentException('Unknown mapper type'),
        };
    }
}
