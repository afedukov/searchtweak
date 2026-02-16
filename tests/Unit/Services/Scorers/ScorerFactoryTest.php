<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\AveragePrecisionScorer;
use App\Services\Scorers\CumulativeGainDetailScorer;
use App\Services\Scorers\CumulativeGainScorer;
use App\Services\Scorers\DiscountedCumulativeGainDetailScorer;
use App\Services\Scorers\DiscountedCumulativeGainScorer;
use App\Services\Scorers\NormalizedDiscountedCumulativeGainDetailScorer;
use App\Services\Scorers\NormalizedDiscountedCumulativeGainScorer;
use App\Services\Scorers\PrecisionScorer;
use App\Services\Scorers\ReciprocalRankScorer;
use App\Services\Scorers\Scorer;
use App\Services\Scorers\ScorerFactory;
use PHPUnit\Framework\TestCase;

class ScorerFactoryTest extends TestCase
{
    public static function scorerTypesProvider(): array
    {
        return [
            ['precision', PrecisionScorer::class],
            ['ap', AveragePrecisionScorer::class],
            ['rr', ReciprocalRankScorer::class],
            ['cg', CumulativeGainScorer::class],
            ['dcg', DiscountedCumulativeGainScorer::class],
            ['ndcg', NormalizedDiscountedCumulativeGainScorer::class],
            ['cg_d', CumulativeGainDetailScorer::class],
            ['dcg_d', DiscountedCumulativeGainDetailScorer::class],
            ['ndcg_d', NormalizedDiscountedCumulativeGainDetailScorer::class],
        ];
    }

    /**
     * @dataProvider scorerTypesProvider
     */
    public function test_creates_correct_scorer(string $type, string $expectedClass): void
    {
        $scorer = ScorerFactory::create($type);

        $this->assertInstanceOf($expectedClass, $scorer);
        $this->assertEquals($type, $scorer->getType());
    }

    public function test_throws_on_invalid_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ScorerFactory::create('invalid');
    }

    public function test_get_scorers_returns_all_nine(): void
    {
        $scorers = ScorerFactory::getScorers();

        $this->assertCount(9, $scorers);

        foreach ($scorers as $type => $scorer) {
            $this->assertInstanceOf(Scorer::class, $scorer);
            $this->assertEquals($type, $scorer->getType());
        }
    }
}
