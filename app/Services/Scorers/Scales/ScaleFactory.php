<?php

namespace App\Services\Scorers\Scales;

class ScaleFactory
{
    public static function create(string $scaleType): Scale
    {
        return match ($scaleType) {
            BinaryScale::SCALE_TYPE => new BinaryScale(),
            GradedScale::SCALE_TYPE => new GradedScale(),
            DetailScale::SCALE_TYPE => new DetailScale(),
            default => throw new \InvalidArgumentException(sprintf('Invalid scale type: %s', $scaleType)),
        };
    }

    /**
     * @return array<Scale>
     */
    public static function getScales(): array
    {
        return array_map(fn (string $scaleClass) => new $scaleClass(), [
            BinaryScale::class,
            GradedScale::class,
            DetailScale::class,
        ]);
    }
}
