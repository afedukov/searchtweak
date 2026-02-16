<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\ReciprocalRankScorer;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class ReciprocalRankScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private ReciprocalRankScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new ReciprocalRankScorer();
        $this->scorer->setTransformers(new Transformers('binary', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('rr', $this->scorer->getType());
    }

    public function test_scale_is_binary(): void
    {
        $this->assertInstanceOf(BinaryScale::class, $this->scorer->getScale());
    }

    public function test_first_relevant_returns_one(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [1], 2 => [0], 3 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(1.0, $result);
    }

    public function test_second_relevant_returns_half(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [1], 3 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(0.5, $result);
    }

    public function test_third_relevant_returns_third(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [0], 3 => [1],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEqualsWithDelta(0.333, $result, 0.001);
    }

    public function test_none_relevant_returns_zero(): void
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

    public function test_name_single_keyword(): void
    {
        $this->assertEquals('RR@%d', $this->scorer->getName(1));
    }

    public function test_name_multiple_keywords(): void
    {
        $this->assertEquals('MRR@%d', $this->scorer->getName(2));
    }
}
