<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\CumulativeGainScorer;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class CumulativeGainScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private CumulativeGainScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new CumulativeGainScorer();
        $this->scorer->setTransformers(new Transformers('graded', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('cg', $this->scorer->getType());
    }

    public function test_scale_is_graded(): void
    {
        $this->assertInstanceOf(GradedScale::class, $this->scorer->getScale());
    }

    public function test_sum_of_graded_values(): void
    {
        // CG = 3 + 2 + 1 + 0 = 6
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [3], 2 => [2], 3 => [1], 4 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 4);

        $this->assertEquals(6.0, $result);
    }

    public function test_all_perfect(): void
    {
        // CG = 3 + 3 + 3 = 9
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [3], 2 => [3], 3 => [3],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(9.0, $result);
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
        $this->assertEquals('CG@%d', $this->scorer->getName());
    }
}
