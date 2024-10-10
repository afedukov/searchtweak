<?php

namespace App\Services\Scorers;

use App\Models\EvaluationKeyword;
use App\Services\Scorers\Scales\BinaryScale;

class ReciprocalRankScorer extends Scorer
{
    protected string $scaleClass = BinaryScale::class;

    public function getType(): string
    {
        return 'rr';
    }

    public function getName(int $keywordsCount = 1): string
    {
        if ($keywordsCount > 1) {
            return 'MRR@%d';
        }

        return 'RR@%d';
    }

    public function getBriefDescription(int $keywordsCount = 1): string
    {
        if ($keywordsCount > 1) {
            return 'Mean Reciprocal Rank';
        }

        return 'Reciprocal Rank';
    }

    public function getDescription(): string
    {
        return 'Reciprocal Rank (RR) measures the rank of the first relevant document. It is the reciprocal of the rank of the first relevant document. If no relevant documents are found, the reciprocal rank is 0. If there is more than one keyword, the Mean Reciprocal Rank (MRR) is calculated.';
    }

    public function calculate(EvaluationKeyword $keyword, int $limit): ?float
    {
        $relevanceValues = $this->getRelevanceValues($keyword, $limit);

        // if there are no graded results, return null
        if (empty(array_filter($relevanceValues, fn ($value) => $value !== null))) {
            return null;
        }

        foreach ($relevanceValues as $position => $relevanceValue) {
            if ((int) $relevanceValue === BinaryScale::RELEVANT) {
                return 1 / $position;
            }
        }

        return 0;
    }
}
