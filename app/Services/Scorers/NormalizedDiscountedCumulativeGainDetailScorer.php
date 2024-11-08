<?php

namespace App\Services\Scorers;

use App\Services\Scorers\Scales\DetailScale;

class NormalizedDiscountedCumulativeGainDetailScorer extends NormalizedDiscountedCumulativeGainScorer
{
    protected string $scaleClass = DetailScale::class;

    public function getType(): string
    {
        return 'ndcg_d';
    }

    public function getName(int $keywordsCount = 1): string
    {
        return 'nDCG(d)@%d';
    }
}
