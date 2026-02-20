<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\AveragePrecisionScorer;
use App\Services\Scorers\CumulativeGainScorer;
use App\Services\Scorers\NormalizedDiscountedCumulativeGainScorer;
use App\Services\Scorers\PrecisionScorer;
use App\Services\Scorers\ReciprocalRankScorer;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

class StrategyThreeAggregationTest extends TestCase
{
    use CreatesScorerTestData;

    public function test_precision_uses_binary_majority_per_snapshot(): void
    {
        $scorer = (new PrecisionScorer())->setTransformers(new Transformers('binary', []));

        $keyword = $this->createKeywordWithFeedbacks([
            1 => [1, 1, 0], // majority relevant => 1
            2 => [0, 0, 1], // majority irrelevant => 0
            3 => [1, 0, null], // tie => null (excluded from denominator)
        ]);

        $this->assertEqualsWithDelta(0.5, $scorer->calculate($keyword, 3), 0.0001);
    }

    public function test_ap_with_tie_before_first_relevant(): void
    {
        $scorer = (new AveragePrecisionScorer())->setTransformers(new Transformers('binary', []));

        $keyword = $this->createKeywordWithFeedbacks([
            1 => [1, 0, null], // tie => null
            2 => [1, 1, 0], // relevant
            3 => [0, 0, 1], // irrelevant
        ]);

        // AP: only relevant at k=2 => (1/2)/1 = 0.5
        $this->assertEqualsWithDelta(0.5, $scorer->calculate($keyword, 3), 0.0001);
    }

    public function test_rr_with_tie_before_first_relevant(): void
    {
        $scorer = (new ReciprocalRankScorer())->setTransformers(new Transformers('binary', []));

        $keyword = $this->createKeywordWithFeedbacks([
            1 => [1, 0, null], // tie => null
            2 => [0, 0, 1], // irrelevant
            3 => [1, 1, 0], // relevant
        ]);

        $this->assertEqualsWithDelta(1 / 3, $scorer->calculate($keyword, 3), 0.0001);
    }

    public function test_cg_uses_averaged_graded_value_from_three_feedbacks(): void
    {
        $scorer = (new CumulativeGainScorer())->setTransformers(new Transformers('graded', []));

        $keyword = $this->createKeywordWithFeedbacks([
            1 => [3, 2, 1], // avg 2
            2 => [1, 1, 1], // avg 1
            3 => [0, 0, 0], // avg 0
        ]);

        $this->assertEqualsWithDelta(3.0, $scorer->calculate($keyword, 3), 0.0001);
    }

    public function test_ndcg_uses_averaged_graded_values_from_three_feedbacks(): void
    {
        $scorer = (new NormalizedDiscountedCumulativeGainScorer())->setTransformers(new Transformers('graded', []));

        $keyword = $this->createKeywordWithFeedbacks([
            1 => [0, 0, 0], // avg 0
            2 => [1, 1, 1], // avg 1
            3 => [3, 2, 1], // avg 2
        ]);

        $result = $scorer->calculate($keyword, 3);

        $this->assertNotNull($result);
        $this->assertGreaterThan(0.0, $result);
        $this->assertLessThan(1.0, $result);
    }
}
