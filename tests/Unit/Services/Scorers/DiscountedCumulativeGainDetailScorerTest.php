<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\DiscountedCumulativeGainDetailScorer;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class DiscountedCumulativeGainDetailScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private DiscountedCumulativeGainDetailScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new DiscountedCumulativeGainDetailScorer();
        $this->scorer->setTransformers(new Transformers('detail', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('dcg_d', $this->scorer->getType());
    }

    public function test_scale_is_detail(): void
    {
        $this->assertInstanceOf(DetailScale::class, $this->scorer->getScale());
    }

    public function test_name(): void
    {
        $this->assertEquals('DCG(d)@%d', $this->scorer->getName());
    }

    public function test_dcg_with_detail_values(): void
    {
        // DCG = 10/log2(2) + 5/log2(3) + 1/log2(4)
        //     = 10 + 3.1546 + 0.5 = 13.6546
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [10], 2 => [5], 3 => [1],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEqualsWithDelta(13.6546, $result, 0.01);
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
