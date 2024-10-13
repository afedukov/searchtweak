<?php

namespace App\Services\Scorers\Scales;

class DetailScale extends Scale
{
    public const string SCALE_TYPE = 'detail';

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
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5',
            6 => '6',
            7 => '7',
            8 => '8',
            9 => '9',
            10 => '10',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getShortcuts(): array
    {
        return [
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5',
            6 => '6',
            7 => '7',
            8 => '8',
            9 => '9',
            10 => '0',
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
