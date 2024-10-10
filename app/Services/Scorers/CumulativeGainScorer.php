<?php

namespace App\Services\Scorers;

use App\Models\EvaluationKeyword;
use App\Services\Scorers\Scales\GradedScale;

class CumulativeGainScorer extends Scorer
{
    protected string $scaleClass = GradedScale::class;

    public function getType(): string
    {
        return 'cg';
    }

    public function getName(int $keywordsCount = 1): string
    {
        return 'CG@%d';
    }

    public function getBriefDescription(int $keywordsCount = 1): string
    {
        return 'Cumulative Gain';
    }

    public function getDescription(): string
    {
        return "Cumulative Gain (CG) measures the effectiveness of a search engine by summing the relevance scores of the documents retrieved. It's important to note that CG does not consider the order of relevant items.";
    }

    public function calculate(EvaluationKeyword $keyword, int $limit): ?float
    {
        $relevanceValues = $this->getRelevanceValues($keyword, $limit);

        $graded = array_filter($relevanceValues, fn ($value) => $value !== null);
        if (empty($graded)) {
            return null;
        }

        return array_sum($graded);
    }
}
