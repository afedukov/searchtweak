<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\NormalizedDiscountedCumulativeGainScorer;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class NormalizedDiscountedCumulativeGainScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private NormalizedDiscountedCumulativeGainScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new NormalizedDiscountedCumulativeGainScorer();
        $this->scorer->setTransformers(new Transformers('graded', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('ndcg', $this->scorer->getType());
    }

    public function test_scale_is_graded(): void
    {
        $this->assertInstanceOf(GradedScale::class, $this->scorer->getScale());
    }

    public function test_perfect_ranking_returns_one(): void
    {
        // Already in ideal order: 3, 2, 1
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [3], 2 => [2], 3 => [1],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEqualsWithDelta(1.0, $result, 0.001);
    }

    public function test_all_zero_returns_zero(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [0], 3 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(0.0, $result);
    }

    public function test_all_same_returns_one(): void
    {
        // All same value: DCG == IDCG
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [2], 2 => [2], 3 => [2],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEqualsWithDelta(1.0, $result, 0.001);
    }

    public function test_reversed_ranking_less_than_one(): void
    {
        // Worst order: 0, 1, 3 instead of ideal 3, 1, 0
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [1], 3 => [3],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertGreaterThan(0.0, $result);
        $this->assertLessThan(1.0, $result);
    }

    public function test_all_null_returns_null(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [null], 2 => [null],
        ]);

        $result = $this->scorer->calculate($keyword, 2);

        $this->assertNull($result);
    }

    public function test_name(): void
    {
        $this->assertEquals('nDCG@%d', $this->scorer->getName());
    }
}
