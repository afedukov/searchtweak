<?php

namespace App\Services\Scorers;

class Err018Scorer extends ErrScorer
{
    public function getType(): string
    {
        return 'err_018';
    }

    public function getName(int $keywordsCount = 1): string
    {
        return 'ERR-0.18@%d';
    }

    public function getBriefDescription(int $keywordsCount = 1): string
    {
        return 'Expected Reciprocal Rank (0.18 exponent)';
    }

    public function getDescription(): string
    {
        return 'ERR-0.18 is a variant of Expected Reciprocal Rank that uses a rank^0.18 position factor instead of 1/rank. This gentler position discount gives more weight to lower-ranked results compared to standard ERR, making it suitable for scenarios where users are willing to scan deeper into result lists.';
    }

    protected function getDiscount(float $pUseful, int $rank): float
    {
        return $pUseful / pow($rank, 0.18);
    }
}
