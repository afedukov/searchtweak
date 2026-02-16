<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\AveragePrecisionScorer;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class AveragePrecisionScorerTest extends TestCase
{
    use CreatesScorerTestData;

    private AveragePrecisionScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new AveragePrecisionScorer();
        $this->scorer->setTransformers(new Transformers('binary', []));
    }

    public function test_type(): void
    {
        $this->assertEquals('ap', $this->scorer->getType());
    }

    public function test_scale_is_binary(): void
    {
        $this->assertInstanceOf(BinaryScale::class, $this->scorer->getScale());
    }

    public function test_known_example(): void
    {
        // Classic IR example: [1, 0, 1, 0, 1]
        // AP = (1/1 + 2/3 + 3/5) / 3 = (1 + 0.6667 + 0.6) / 3 = 0.7556
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [1], 2 => [0], 3 => [1], 4 => [0], 5 => [1],
        ]);

        $result = $this->scorer->calculate($keyword, 5);

        $this->assertEqualsWithDelta(0.7556, $result, 0.001);
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

    public function test_all_null_returns_null(): void
    {
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [null], 2 => [null],
        ]);

        $result = $this->scorer->calculate($keyword, 2);

        $this->assertNull($result);
    }

    public function test_single_relevant_at_first(): void
    {
        // AP = (1/1) / 1 = 1.0
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [1], 2 => [0], 3 => [0],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEquals(1.0, $result);
    }

    public function test_single_relevant_at_last(): void
    {
        // AP = (1/3) / 1 = 0.333
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0], 2 => [0], 3 => [1],
        ]);

        $result = $this->scorer->calculate($keyword, 3);

        $this->assertEqualsWithDelta(0.333, $result, 0.001);
    }

    public function test_name_single_keyword(): void
    {
        $this->assertEquals('AP@%d', $this->scorer->getName(1));
    }

    public function test_name_multiple_keywords(): void
    {
        $this->assertEquals('MAP@%d', $this->scorer->getName(2));
    }
}
