<?php

namespace App\Services\Scorers;

class ScorerFactory
{
    public const array SCORER_TYPES = [
        'precision' => PrecisionScorer::class,
        'ap' => AveragePrecisionScorer::class,
        'rr' => ReciprocalRankScorer::class,
        'cg' => CumulativeGainScorer::class,
        'dcg' => DiscountedCumulativeGainScorer::class,
        'ndcg' => NormalizedDiscountedCumulativeGainScorer::class,
        'cg_d' => CumulativeGainDetailScorer::class,
        'dcg_d' => DiscountedCumulativeGainDetailScorer::class,
        'ndcg_d' => NormalizedDiscountedCumulativeGainDetailScorer::class,
    ];

    public static function create(string $type): Scorer
    {
        return new (self::SCORER_TYPES[$type] ?? throw new \InvalidArgumentException("Invalid scorer type: $type"))();
    }

    /**
     * @return array<string, Scorer>
     */
    public static function getScorers(): array
    {
        $scorers = [];

        foreach (array_keys(self::SCORER_TYPES) as $type) {
            $scorers[$type] = self::create($type);
        }

        return $scorers;
    }
}
