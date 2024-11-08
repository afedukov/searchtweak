<?php

namespace App\Services\Scorers;

use App\Services\Scorers\Scales\DetailScale;

class DiscountedCumulativeGainDetailScorer extends DiscountedCumulativeGainScorer
{
    protected string $scaleClass = DetailScale::class;

    public function getType(): string
    {
        return 'dcg_d';
    }

    public function getName(int $keywordsCount = 1): string
    {
        return 'DCG(d)@%d';
    }
}
