<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\PrecisionScorer;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class PrecisionScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private PrecisionScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new PrecisionScorer();
        $this->scorer->setTransformers(new Transformers('binary', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('precision', $this->scorer->getType());
    }

    public function test_scale_is_binary(): void
    {
        $this->assertInstanceOf(BinaryScale::class, $this->scorer->getScale());
    }

    public function test_all_relevant_returns_one(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [1], 2 => [1], 3 => [1],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(1.0, $result);
    }

    public function test_none_relevant_returns_zero(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [0], 3 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(0.0, $result);
    }

    public function test_mixed_relevance(): void
    {
        // 2 relevant out of 4
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [1], 2 => [0], 3 => [1], 4 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 4);

        $this->assertEquals(0.5, $result);
    }

    public function test_null_grades_are_excluded(): void
    {
        // 1 relevant, 1 irrelevant, 1 ungraded
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [1], 2 => [0], 3 => [null],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(0.5, $result);
    }

    public function test_all_null_returns_null(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [null], 2 => [null],
        ]);

        $result = $this->scorer->calculate($keyword, 2);

        $this->assertNull($result);
    }

    public function test_respects_limit(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [1], 2 => [1], 3 => [0], 4 => [0], 5 => [0],
        ]);

        // Limit to top 2: both relevant
        $result = $this->scorer->calculate($keyword, 2);

        $this->assertEquals(1.0, $result);
    }

    public function test_name_single_keyword(): void
    {
        $this->assertEquals('P@%d', $this->scorer->getName(1));
    }

    public function test_name_multiple_keywords(): void
    {
        $this->assertEquals('MP@%d', $this->scorer->getName(2));
    }
}
