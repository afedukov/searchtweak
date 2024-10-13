<?php

namespace App\Services\Scorers\Scales;

class GradedScale extends Scale
{
    public const string SCALE_TYPE = 'graded';

    public const int POOR = 0;
    public const int FAIR = 1;
    public const int GOOD = 2;
    public const int PERFECT = 3;

    public function getName(): string
    {
        return 'Graded Scale';
    }

    /**
     * @return array<int, string>
     */
    public function getValues(): array
    {
        return [
            self::POOR => 'Poor',
            self::FAIR => 'Fair',
            self::GOOD => 'Good',
            self::PERFECT => 'Perfect',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getShortcuts(): array
    {
        return [
            self::POOR => '1',
            self::FAIR => '2',
            self::GOOD => '3',
            self::PERFECT => '4',
        ];
    }

    public function getValue(array $grades): ?float
    {
        if (empty($grades)) {
            return null;
        }

        return array_sum($grades) / count($grades);
    }
}
