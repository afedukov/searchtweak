<?php

namespace Tests\Unit\Rules;

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use App\Rules\EvaluationMetricRule;
use PHPUnit\Framework\TestCase;

class EvaluationMetricRuleTest extends TestCase
{
    private EvaluationMetricRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new EvaluationMetricRule();
    }

    private function validate(array $data, array $metrics): ?string
    {
        $this->rule->setData($data);

        $error = null;
        $this->rule->validate('metrics', $metrics, function ($message) use (&$error) {
            $error = $message;
        });

        return $error;
    }

    public function test_valid_metrics_pass(): void
    {
        $error = $this->validate(
            ['evaluation' => [SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING, SearchEvaluation::FIELD_MAX_NUM_RESULTS => 50]],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 10],
            ]
        );

        $this->assertNull($error);
    }

    public function test_fails_when_evaluation_is_not_pending(): void
    {
        $error = $this->validate(
            ['evaluation' => [SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_ACTIVE]],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 10],
            ]
        );

        $this->assertEquals('Evaluation can only be updated when in pending status.', $error);
    }

    public function test_fails_without_scorer_type(): void
    {
        $error = $this->validate(
            ['evaluation' => [SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING]],
            [
                [EvaluationMetric::FIELD_NUM_RESULTS => 10],
            ]
        );

        $this->assertEquals('Metric scorer type is required.', $error);
    }

    public function test_fails_without_num_results(): void
    {
        $error = $this->validate(
            ['evaluation' => [SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING]],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision'],
            ]
        );

        $this->assertEquals('Metric number of results is required.', $error);
    }

    public function test_fails_with_invalid_scorer_type(): void
    {
        $error = $this->validate(
            ['evaluation' => [SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING]],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'nonexistent', EvaluationMetric::FIELD_NUM_RESULTS => 10],
            ]
        );

        $this->assertEquals('Invalid metric scorer type.', $error);
    }

    public function test_fails_when_num_results_exceeds_max(): void
    {
        $error = $this->validate(
            ['evaluation' => [
                SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING,
                SearchEvaluation::FIELD_MAX_NUM_RESULTS => 5,
            ]],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 10],
            ]
        );

        $this->assertStringContainsString('less than or equal to', $error);
    }

    public function test_fails_on_duplicate_metric(): void
    {
        $error = $this->validate(
            ['evaluation' => [SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING]],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 10],
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 10],
            ]
        );

        $this->assertEquals('Duplicate metric found.', $error);
    }

    public function test_allows_same_scorer_type_different_num_results(): void
    {
        $error = $this->validate(
            ['evaluation' => [SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING]],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 5],
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 10],
            ]
        );

        $this->assertNull($error);
    }

    public function test_fails_when_num_results_out_of_range_too_low(): void
    {
        $error = $this->validate(
            ['evaluation' => [SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING]],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 0],
            ]
        );

        $this->assertEquals('Metric number of results must be an integer between 1 and 50.', $error);
    }

    public function test_fails_when_num_results_out_of_range_too_high(): void
    {
        $error = $this->validate(
            ['evaluation' => [SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING]],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 51],
            ]
        );

        $this->assertEquals('Metric number of results must be an integer between 1 and 50.', $error);
    }

    public function test_passes_when_no_evaluation_status(): void
    {
        // No evaluation in data => new evaluation, should pass
        $error = $this->validate(
            [],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 10],
            ]
        );

        $this->assertNull($error);
    }

    public function test_multiple_valid_metrics_different_types(): void
    {
        $error = $this->validate(
            ['evaluation' => [SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING]],
            [
                [EvaluationMetric::FIELD_SCORER_TYPE => 'precision', EvaluationMetric::FIELD_NUM_RESULTS => 10],
                [EvaluationMetric::FIELD_SCORER_TYPE => 'ap', EvaluationMetric::FIELD_NUM_RESULTS => 10],
                [EvaluationMetric::FIELD_SCORER_TYPE => 'dcg', EvaluationMetric::FIELD_NUM_RESULTS => 5],
            ]
        );

        $this->assertNull($error);
    }
}
