<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\DiscountedCumulativeGainScorer;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class DiscountedCumulativeGainScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private DiscountedCumulativeGainScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new DiscountedCumulativeGainScorer();
        $this->scorer->setTransformers(new Transformers('graded', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('dcg', $this->scorer->getType());
    }

    public function test_scale_is_graded(): void
    {
        $this->assertInstanceOf(GradedScale::class, $this->scorer->getScale());
    }

    public function test_dcg_log2_discount(): void
    {
        // DCG = 3/log2(2) + 2/log2(3) + 1/log2(4) + 0/log2(5)
        //     = 3/1 + 2/1.585 + 1/2 + 0
        //     = 3 + 1.2619 + 0.5 = 4.7619
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [3], 2 => [2], 3 => [1], 4 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 4);

        $this->assertEqualsWithDelta(4.7619, $result, 0.001);
    }

    public function test_single_item(): void
    {
        // DCG = 3/log2(2) = 3/1 = 3
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [3],
        ]);

        $result = $this->scorer->calculate($keyword, 1);

        $this->assertEquals(3.0, $result);
    }

    public function test_all_zero(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [0], 3 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(0.0, $result);
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
        $this->assertEquals('DCG@%d', $this->scorer->getName());
    }
}
