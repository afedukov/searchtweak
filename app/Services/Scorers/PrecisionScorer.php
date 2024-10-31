<?php

namespace App\Services\Scorers;

use App\Models\EvaluationKeyword;
use App\Services\Scorers\Scales\BinaryScale;

class PrecisionScorer extends Scorer
{
    protected string $scaleClass = BinaryScale::class;

    public function getType(): string
    {
        return 'precision';
    }

    public function getName(int $keywordsCount = 1): string
    {
        if ($keywordsCount > 1) {
            return 'MP@%d';
        }

        return 'P@%d';
    }

    public function getBriefDescription(int $keywordsCount = 1): string
    {
        if ($keywordsCount > 1) {
            return 'Mean Precision';
        }

        return 'Precision';
    }

    public function getDescription(): string
    {
        return "Precision (P) measures the relevance of the entire result set. It is the fraction of retrieved documents that are relevant to the user's information need. If there is more than one keyword, the Mean Precision (MP) is calculated.";
    }

    public function calculate(EvaluationKeyword $keyword, int $limit): ?float
    {
        $relevanceValues = $this->getRelevanceValues($keyword, $limit);

        $graded = array_filter($relevanceValues, fn ($value) => $value !== null);
        if (empty($graded)) {
            return null;
        }

        $relevantCount = array_sum($graded);
        $gradedCount = count($graded);

        return $gradedCount > 0 ? $relevantCount / $gradedCount : null;
    }
}
