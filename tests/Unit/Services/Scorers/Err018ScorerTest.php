<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\Err018Scorer;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class Err018ScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private Err018Scorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new Err018Scorer();
        $this->scorer->setTransformers(new Transformers('graded', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('err_018', $this->scorer->getType());
    }

    public function test_name(): void
    {
        $this->assertEquals('ERR-0.18@%d', $this->scorer->getName());
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
        // Single perfect result (grade=3): pUseful = 7/8
        // discount = pUseful / pow(1, 0.18) = 7/8 / 1 = 0.875
        // ERR = 0.875 * 1.0 = 0.875
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [3],
        ]);

        $result = $this->scorer->calculate($keyword, 1);

        $this->assertEqualsWithDelta(0.875, $result, 0.001);
    }

    public function test_discount_differs_from_standard_err(): void
    {
        // At rank 2, ERR-0.18 uses pUseful / 2^0.18 instead of pUseful / 2
        // So ERR-0.18 gives more weight to lower ranks than standard ERR (gentler discount)
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [3],
        ]);

        $standardScorer = new \App\Services\Scorers\ErrScorer();
        $standardScorer->setTransformers(new Transformers('graded', []));

        $errStandard = $standardScorer->calculate($keyword, 2);
        $err018 = $this->scorer->calculate($keyword, 2);

        // ERR-0.18 should give higher value since 1/rank^0.18 > 1/rank for rank > 1
        $this->assertGreaterThan($errStandard, $err018);
    }

    public function test_known_value_rank_two(): void
    {
        // grade=0 at rank 1, grade=3 at rank 2
        // Rank 1: pUseful = 0, contrib = 0, trust = 1.0
        // Rank 2: pUseful = 7/8, discount = 7/8 / 2^0.18 ≈ 7/8 / 1.13269 ≈ 0.77259
        // contrib = 0.77259 * 1.0 = 0.77259
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [3],
        ]);

        $result = $this->scorer->calculate($keyword, 2);

        $expected = (7 / 8) / pow(2, 0.18);
        $this->assertEqualsWithDelta($expected, $result, 0.001);
    }

    public function test_result_bounded_by_one(): void
    {
        // ERR should never exceed 1.0
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [3], 2 => [3], 3 => [3], 4 => [3], 5 => [3],
        ]);

        $result = $this->scorer->calculate($keyword, 5);

        $this->assertLessThanOrEqual(1.0, $result);
    }
}
