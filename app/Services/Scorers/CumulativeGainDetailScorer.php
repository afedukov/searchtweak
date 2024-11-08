<?php

namespace App\Services\Scorers;

use App\Services\Scorers\Scales\DetailScale;

class CumulativeGainDetailScorer extends CumulativeGainScorer
{
    protected string $scaleClass = DetailScale::class;

    public function getType(): string
    {
        return 'cg_d';
    }

    public function getName(int $keywordsCount = 1): string
    {
        return 'CG(d)@%d';
    }
}
