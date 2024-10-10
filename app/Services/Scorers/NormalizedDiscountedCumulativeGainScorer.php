<?php

namespace App\Services\Scorers;

use App\Models\EvaluationKeyword;
use App\Services\Scorers\Scales\GradedScale;

class NormalizedDiscountedCumulativeGainScorer extends Scorer
{
    protected string $scaleClass = GradedScale::class;

    public function getType(): string
    {
        return 'ndcg';
    }

    public function getName(int $keywordsCount = 1): string
    {
        return 'nDCG@%d';
    }

    public function getBriefDescription(int $keywordsCount = 1): string
    {
        return 'Normalized Discounted Cumulative Gain';
    }

    public function getDescription(): string
    {
        return 'Normalized Discounted Cumulative Gain (nDCG) is a metric used to evaluate the effectiveness of ranked lists, such as search engine results or recommendation systems. It accounts for both the relevance and the positioning of items in the list, providing a normalized measure that facilitates comparison across different queries or systems, thereby offering insights into the quality of the ranked results.';
    }

    public function calculate(EvaluationKeyword $keyword, int $limit): ?float
    {
        $relevanceValues = array_values($this->getRelevanceValues($keyword, $limit));

        $graded = array_filter($relevanceValues, fn ($value) => $value !== null);
        if (empty($graded)) {
            return null;
        }

        $dcg = $this->calculateDcg($relevanceValues);

        arsort($relevanceValues);

        $idealDcg = $this->calculateDcg(array_values($relevanceValues));

        return $idealDcg == 0 ? 0 : $dcg / $idealDcg;
    }

    private function calculateDcg(array $values): float
    {
        $dcg = 0;
        $total = count($values);

        for ($i = 0; $i < $total; $i++) {
            $dcg += ($values[$i] ?? 0) / log($i + 2, 2);
        }

        return $dcg;
    }
}
