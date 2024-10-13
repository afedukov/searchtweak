<?php

namespace App\Services\Scorers\Scales;

class BinaryScale extends Scale
{
    public const string SCALE_TYPE = 'binary';

    public const int IRRELEVANT = 0;
    public const int RELEVANT = 1;

    public function getName(): string
    {
        return 'Binary Scale';
    }

    /**
     * @return array<int, string>
     */
    public function getValues(): array
    {
        return [
            self::IRRELEVANT => 'Irrelevant',
            self::RELEVANT => 'Relevant',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getShortcuts(): array
    {
        return [
            self::IRRELEVANT => '1',
            self::RELEVANT => '2',
        ];
    }

    /**
     * Return prevailing value from the given grades. E.g. if there are more 'Relevant' grades than 'Irrelevant' grades,
     * the prevailing value is 'Relevant'. If there are equal number of 'Relevant' and 'Irrelevant' grades, return null.
     *
     * @param array $grades
     *
     * @return float|null
     */
    public function getValue(array $grades): ?float
    {
        if (empty($grades)) {
            return null;
        }

        $counts = array_count_values($grades);

        if (($counts[self::RELEVANT] ?? 0) > ($counts[self::IRRELEVANT] ?? 0)) {
            return self::RELEVANT;
        } elseif (($counts[self::RELEVANT] ?? 0) < ($counts[self::IRRELEVANT] ?? 0)) {
            return self::IRRELEVANT;
        }

        return null;
    }
}
