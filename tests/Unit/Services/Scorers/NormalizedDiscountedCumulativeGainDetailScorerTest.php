<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\NormalizedDiscountedCumulativeGainDetailScorer;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class NormalizedDiscountedCumulativeGainDetailScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private NormalizedDiscountedCumulativeGainDetailScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new NormalizedDiscountedCumulativeGainDetailScorer();
        $this->scorer->setTransformers(new Transformers('detail', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('ndcg_d', $this->scorer->getType());
    }

    public function test_scale_is_detail(): void
    {
        $this->assertInstanceOf(DetailScale::class, $this->scorer->getScale());
    }

    public function test_name(): void
    {
        $this->assertEquals('nDCG(d)@%d', $this->scorer->getName());
    }

    public function test_perfect_ranking_returns_one(): void
    {
        // Already in ideal order: 10, 5, 1
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [10], 2 => [5], 3 => [1],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEqualsWithDelta(1.0, $result, 0.001);
    }

    public function test_all_same_returns_one(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [5], 2 => [5], 3 => [5],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEqualsWithDelta(1.0, $result, 0.001);
    }

    public function test_all_null_returns_null(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [null], 2 => [null],
        ]);

        $result = $this->scorer->calculate($keyword, 2);

        $this->assertNull($result);
    }
}
