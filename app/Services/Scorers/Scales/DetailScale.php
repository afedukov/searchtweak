<?php

namespace App\Services\Scorers\Scales;

class DetailScale extends Scale
{
    public const string SCALE_TYPE = 'detail';

    public const int V_1 = 1;
    public const int V_2 = 2;
    public const int V_3 = 3;
    public const int V_4 = 4;
    public const int V_5 = 5;
    public const int V_6 = 6;
    public const int V_7 = 7;
    public const int V_8 = 8;
    public const int V_9 = 9;
    public const int V_10 = 10;

    public function getName(): string
    {
        return 'Detail Scale';
    }

    /**
     * @return array<int, string>
     */
    public function getValues(): array
    {
        return [
            self::V_1 => '1',
            self::V_2 => '2',
            self::V_3 => '3',
            self::V_4 => '4',
            self::V_5 => '5',
            self::V_6 => '6',
            self::V_7 => '7',
            self::V_8 => '8',
            self::V_9 => '9',
            self::V_10 => '10',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getShortcuts(): array
    {
        return [
            self::V_1 => '1',
            self::V_2 => '2',
            self::V_3 => '3',
            self::V_4 => '4',
            self::V_5 => '5',
            self::V_6 => '6',
            self::V_7 => '7',
            self::V_8 => '8',
            self::V_9 => '9',
            self::V_10 => '0',
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
