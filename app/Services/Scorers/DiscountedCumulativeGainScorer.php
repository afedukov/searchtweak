<?php

namespace App\Services\Scorers;

use App\Models\EvaluationKeyword;
use App\Services\Scorers\Scales\GradedScale;

class DiscountedCumulativeGainScorer extends Scorer
{
    protected string $scaleClass = GradedScale::class;

    public function getType(): string
    {
        return 'dcg';
    }

    public function getName(int $keywordsCount = 1): string
    {
        return 'DCG@%d';
    }

    public function getBriefDescription(int $keywordsCount = 1): string
    {
        return 'Discounted Cumulative Gain';
    }

    public function getDescription(): string
    {
        return "Discounted Cumulative Gain (DCG) assesses the effectiveness of ranked lists by summing the relevance scores of documents, giving more weight to those at higher positions, reflecting user preferences for relevant items at the top of the list.";
    }

    public function calculate(EvaluationKeyword $keyword, int $limit): ?float
    {
        $relevanceValues = $this->getRelevanceValues($keyword, $limit);

        $graded = array_filter($relevanceValues, fn ($value) => $value !== null);
        if (empty($graded)) {
            return null;
        }

        $dcg = 0;
        $total = count($relevanceValues);

        for ($i = 1; $i <= $total; $i++) {
            $dcg += ($relevanceValues[$i] ?? 0) / log($i + 1, 2);
        }

        return $dcg;
    }
}
