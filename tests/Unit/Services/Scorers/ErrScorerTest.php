<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\ErrScorer;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class ErrScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private ErrScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new ErrScorer();
        $this->scorer->setTransformers(new Transformers('graded', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('err', $this->scorer->getType());
    }

    public function test_name(): void
    {
        $this->assertEquals('ERR@%d', $this->scorer->getName());
    }

    public function test_scale_is_graded(): void
    {
        $this->assertInstanceOf(GradedScale::class, $this->scorer->getScale());
    }

    public function test_all_null_returns_null(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [null], 2 => [null],
        ]);

        $result = $this->scorer->calculate($keyword, 2);

        $this->assertNull($result);
    }

    public function test_all_zero_returns_zero(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [0], 3 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(0.0, $result);
    }

    public function test_perfect_at_rank_one(): void
    {
        // Single perfect result (grade=3): pUseful = (2^3 - 1) / 2^3 = 7/8
        // ERR = 7/8 * 1/1 * 1.0 = 0.875
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [3],
        ]);

        $result = $this->scorer->calculate($keyword, 1);

        $this->assertEqualsWithDelta(0.875, $result, 0.001);
    }

    public function test_cascade_probability(): void
    {
        // Two results: grade=0 at rank 1, grade=3 at rank 2
        // Rank 1: pUseful = (2^0 - 1) / 2^3 = 0, contribution = 0, trust stays 1.0
        // Rank 2: pUseful = 7/8, contribution = (7/8) / 2 * 1.0 = 0.4375
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [3],
        ]);

        $result = $this->scorer->calculate($keyword, 2);

        $this->assertEqualsWithDelta(0.4375, $result, 0.001);
    }

    public function test_known_values(): void
    {
        // grades: 3, 2, 1, 0 (maxGrade = 3)
        // pUseful: 7/8, 3/8, 1/8, 0
        // Rank 1: trust=1.0, contrib = 7/8 / 1 * 1.0 = 0.875, trust = 1/8
        // Rank 2: trust=1/8, contrib = 3/8 / 2 * 1/8 = 0.0234375, trust = 1/8 * 5/8 = 5/64
        // Rank 3: trust=5/64, contrib = 1/8 / 3 * 5/64 = 0.003255..., trust = 5/64 * 7/8
        // Rank 4: trust=35/512, contrib = 0
        // Total ≈ 0.875 + 0.0234375 + 0.003255... = 0.90169...
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [3], 2 => [2], 3 => [1], 4 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 4);

        $this->assertEqualsWithDelta(0.9017, $result, 0.001);
    }

    public function test_higher_relevance_at_top_yields_higher_err(): void
    {
        $goodFirst = $this->createKeywordWithFeedbacks([
            1 => [3], 2 => [0],
        ]);

        $goodSecond = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [3],
        ]);

        $resultGoodFirst = $this->scorer->calculate($goodFirst, 2);
        $resultGoodSecond = $this->scorer->calculate($goodSecond, 2);

        $this->assertGreaterThan($resultGoodSecond, $resultGoodFirst);
    }
}
