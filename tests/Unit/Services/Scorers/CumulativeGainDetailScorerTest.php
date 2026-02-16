<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\CumulativeGainDetailScorer;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class CumulativeGainDetailScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private CumulativeGainDetailScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new CumulativeGainDetailScorer();
        $this->scorer->setTransformers(new Transformers('detail', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('cg_d', $this->scorer->getType());
    }

    public function test_scale_is_detail(): void
    {
        $this->assertInstanceOf(DetailScale::class, $this->scorer->getScale());
    }

    public function test_name(): void
    {
        $this->assertEquals('CG(d)@%d', $this->scorer->getName());
    }

    public function test_sum_of_detail_values(): void
    {
        // CG = 10 + 5 + 1 = 16
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [10], 2 => [5], 3 => [1],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(16.0, $result);
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
