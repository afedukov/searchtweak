<?php

namespace App\Services\Scorers;

use App\Models\EvaluationKeyword;
use App\Services\Scorers\Scales\GradedScale;

class ErrScorer extends Scorer
{
    protected string $scaleClass = GradedScale::class;

    public function getType(): string
    {
        return 'err';
    }

    public function getName(int $keywordsCount = 1): string
    {
        return 'ERR@%d';
    }

    public function getBriefDescription(int $keywordsCount = 1): string
    {
        return 'Expected Reciprocal Rank';
    }

    public function getDescription(): string
    {
        return 'Expected Reciprocal Rank (ERR) models a cascade user who scans results from top to bottom and stops when satisfied. It combines a position discount (1/rank) with a probability-of-satisfaction gain function, providing a single metric that captures both relevance and rank position in a user-centric way.';
    }

    public function calculate(EvaluationKeyword $keyword, int $limit): ?float
    {
        $relevanceValues = array_values($this->getRelevanceValues($keyword, $limit));

        $graded = array_filter($relevanceValues, fn ($value) => $value !== null);
        if (empty($graded)) {
            return null;
        }

        $maxGrade = max(array_keys($this->getScale()->getValues()));

        $err = 0.0;
        $trust = 1.0;

        for ($i = 0; $i < count($relevanceValues); $i++) {
            $grade = $relevanceValues[$i] ?? 0;
            $pUseful = (pow(2, $grade) - 1) / pow(2, $maxGrade);
            $rank = $i + 1;

            $err += $this->getDiscount($pUseful, $rank) * $trust;
            $trust *= (1 - $pUseful);
        }

        return $err;
    }

    protected function getDiscount(float $pUseful, int $rank): float
    {
        return $pUseful / $rank;
    }
}
