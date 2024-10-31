<?php

namespace App\Services\Scorers;

use App\Models\EvaluationKeyword;
use App\Services\Scorers\Scales\BinaryScale;

class AveragePrecisionScorer extends Scorer
{
    protected string $scaleClass = BinaryScale::class;

    public function getType(): string
    {
        return 'ap';
    }

    public function getName(int $keywordsCount = 1): string
    {
        if ($keywordsCount > 1) {
            return 'MAP@%d';
        }

        return 'AP@%d';
    }

    public function getBriefDescription(int $keywordsCount = 1): string
    {
        if ($keywordsCount > 1) {
            return 'Mean Average Precision';
        }

        return 'Average Precision';
    }

    public function getDescription(): string
    {
        return 'Average Precision (AP) measures the relevance for a user scanning results sequentially. It is similar to precision but weights the ranking so that a 0 in rank 1 reduces the score more than a 0 in rank 5. If there is more than one keyword, the Mean Average Precision (MAP) is calculated.';
    }

    public function calculate(EvaluationKeyword $keyword, int $limit): ?float
    {
        $relevanceValues = $this->getRelevanceValues($keyword, $limit);
        if (empty(array_filter($relevanceValues, fn ($value) => $value !== null))) {
            return null;
        }

        $total = count($relevanceValues);
        $relevantCount = 0;
        $sum = 0;

        for ($k = 1; $k <= $total; $k++) {
            if ((int) $relevanceValues[$k] === BinaryScale::RELEVANT) {
                $relevantCount++;

                $relK = 0;
                for ($i = 1; $i <= $k; $i++) {
                    if ((int) $relevanceValues[$i] === BinaryScale::RELEVANT) {
                        $relK++;
                    }
                }

                $sum += $relK / $k;
            }
        }

        return $relevantCount > 0 ? $sum / $relevantCount : 0;
    }
}
