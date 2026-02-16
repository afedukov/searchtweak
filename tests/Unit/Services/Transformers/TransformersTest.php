<?php

namespace Tests\Unit\Services\Transformers;

use App\Models\EvaluationMetric;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;

class TransformersTest extends TestCase
{
    public function test_transform_same_scale_returns_same_value(): void
    {
        $transformers = new Transformers('binary', []);

        $this->assertEquals(1, $transformers->transform('binary', 1));
    }

    public function test_transform_to_different_scale(): void
    {
        $transformers = new Transformers('binary', [
            'graded' => [
                BinaryScale::IRRELEVANT => GradedScale::POOR,
                BinaryScale::RELEVANT => GradedScale::PERFECT,
            ],
        ]);

        $this->assertEquals(GradedScale::POOR, $transformers->transform('graded', BinaryScale::IRRELEVANT));
        $this->assertEquals(GradedScale::PERFECT, $transformers->transform('graded', BinaryScale::RELEVANT));
    }

    public function test_transform_throws_on_missing_rule(): void
    {
        $transformers = new Transformers('binary', []);

        $this->expectException(\InvalidArgumentException::class);

        $transformers->transform('graded', 0);
    }

    public function test_is_empty(): void
    {
        $transformers = new Transformers('binary', []);

        $this->assertTrue($transformers->isEmpty());
        $this->assertFalse($transformers->isNotEmpty());
    }

    public function test_is_not_empty(): void
    {
        $transformers = new Transformers('binary', ['graded' => [0 => 0, 1 => 3]]);

        $this->assertFalse($transformers->isEmpty());
        $this->assertTrue($transformers->isNotEmpty());
    }

    public function test_to_array_from_array_roundtrip(): void
    {
        $original = new Transformers('graded', [
            'binary' => [0 => 0, 1 => 1, 2 => 1, 3 => 1],
        ]);

        $array = $original->toArray();
        $restored = Transformers::fromArray($array);

        $this->assertEquals($original->toArray(), $restored->toArray());
    }

    public function test_from_array_with_empty_rules(): void
    {
        $transformers = Transformers::fromArray(['scale_type' => 'binary']);

        $this->assertTrue($transformers->isEmpty());
        $this->assertEquals('binary', $transformers->getScaleType());
    }

    public function test_equals_both_empty(): void
    {
        $a = new Transformers('binary', []);
        $b = new Transformers('graded', []);

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_same_rules(): void
    {
        $rules = ['graded' => [0 => 0, 1 => 3]];
        $a = new Transformers('binary', $rules);
        $b = new Transformers('binary', $rules);

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_different_rules(): void
    {
        $a = new Transformers('binary', ['graded' => [0 => 0, 1 => 3]]);
        $b = new Transformers('binary', ['graded' => [0 => 1, 1 => 2]]);

        $this->assertFalse($a->equals($b));
    }

    public function test_equals_different_scale_types(): void
    {
        $rules = ['detail' => [0 => 1, 1 => 10]];
        $a = new Transformers('binary', $rules);
        $b = new Transformers('graded', $rules);

        $this->assertFalse($a->equals($b));
    }

    public function test_get_rules(): void
    {
        $rules = ['graded' => [0 => 0, 1 => 3]];
        $transformers = new Transformers('binary', $rules);

        $this->assertEquals($rules, $transformers->getRules());
    }

    public function test_get_scale_type(): void
    {
        $transformers = new Transformers('detail', []);

        $this->assertEquals('detail', $transformers->getScaleType());
    }

    public function test_get_default_form_transformers_returns_all_six_pairs(): void
    {
        $defaults = Transformers::getDefaultFormTransformers();

        $this->assertIsArray($defaults);
        // 3 scale types × 2 directions = 6 pairs
        $this->assertCount(6, $defaults);

        // Check all expected keys exist
        $this->assertArrayHasKey('binary_graded', $defaults);
        $this->assertArrayHasKey('binary_detail', $defaults);
        $this->assertArrayHasKey('graded_binary', $defaults);
        $this->assertArrayHasKey('graded_detail', $defaults);
        $this->assertArrayHasKey('detail_binary', $defaults);
        $this->assertArrayHasKey('detail_graded', $defaults);
    }

    public function test_get_default_form_transformers_values_are_strings(): void
    {
        $defaults = Transformers::getDefaultFormTransformers();

        foreach ($defaults as $key => $value) {
            $this->assertIsString($value, "Expected string for key '$key'");
            $this->assertNotEmpty($value, "Expected non-empty string for key '$key'");
        }
    }

    public function test_get_default_form_transformers_format(): void
    {
        $defaults = Transformers::getDefaultFormTransformers();

        // e.g. "0: 0\n1: 3" for binary_graded
        $binaryToGraded = $defaults['binary_graded'];
        $lines = explode("\n", $binaryToGraded);

        // Binary has 2 values (0, 1), so 2 lines
        $this->assertCount(2, $lines);

        foreach ($lines as $line) {
            $this->assertMatchesRegularExpression('/^\d+: \d+$/', $line);
        }
    }

    public function test_to_form_array(): void
    {
        $transformers = new Transformers(BinaryScale::SCALE_TYPE, [
            GradedScale::SCALE_TYPE => [
                BinaryScale::IRRELEVANT => GradedScale::POOR,
                BinaryScale::RELEVANT => GradedScale::PERFECT,
            ],
        ]);

        $formArray = $transformers->toFormArray();

        $this->assertArrayHasKey('binary_graded', $formArray);
        $this->assertIsString($formArray['binary_graded']);

        // Should contain 2 lines: "0: 0" and "1: 3"
        $lines = explode("\n", $formArray['binary_graded']);
        $this->assertCount(2, $lines);
        $this->assertEquals('0: 0', $lines[0]);
        $this->assertEquals('1: 3', $lines[1]);
    }

    public function test_to_form_array_multiple_destinations(): void
    {
        $transformers = new Transformers(BinaryScale::SCALE_TYPE, [
            GradedScale::SCALE_TYPE => [
                BinaryScale::IRRELEVANT => GradedScale::POOR,
                BinaryScale::RELEVANT => GradedScale::PERFECT,
            ],
            DetailScale::SCALE_TYPE => [
                BinaryScale::IRRELEVANT => DetailScale::V_1,
                BinaryScale::RELEVANT => DetailScale::V_10,
            ],
        ]);

        $formArray = $transformers->toFormArray();

        $this->assertCount(2, $formArray);
        $this->assertArrayHasKey('binary_graded', $formArray);
        $this->assertArrayHasKey('binary_detail', $formArray);
    }

    public function test_to_form_array_empty_rules(): void
    {
        $transformers = new Transformers(BinaryScale::SCALE_TYPE, []);

        $formArray = $transformers->toFormArray();

        $this->assertIsArray($formArray);
        $this->assertEmpty($formArray);
    }

    public function test_create_parses_transformer_strings(): void
    {
        $metrics = [
            [EvaluationMetric::FIELD_SCORER_TYPE => 'precision'],  // BinaryScale
            [EvaluationMetric::FIELD_SCORER_TYPE => 'cg'],         // GradedScale
        ];

        $transformerStrings = [
            'binary_graded' => "0: 0\n1: 3",
        ];

        $result = Transformers::create($metrics, BinaryScale::SCALE_TYPE, $transformerStrings);

        $this->assertEquals(BinaryScale::SCALE_TYPE, $result->getScaleType());
        $this->assertFalse($result->isEmpty());
        $this->assertEquals(GradedScale::POOR, $result->transform(GradedScale::SCALE_TYPE, BinaryScale::IRRELEVANT));
        $this->assertEquals(GradedScale::PERFECT, $result->transform(GradedScale::SCALE_TYPE, BinaryScale::RELEVANT));
    }

    public function test_create_ignores_non_matching_source_scale(): void
    {
        $metrics = [
            [EvaluationMetric::FIELD_SCORER_TYPE => 'precision'],
        ];

        // Key starts with "graded_" but scaleType is "binary" — should be ignored
        $transformerStrings = [
            'graded_binary' => "0: 0\n1: 1\n2: 1\n3: 1",
        ];

        $result = Transformers::create($metrics, BinaryScale::SCALE_TYPE, $transformerStrings);

        $this->assertTrue($result->isEmpty());
    }

    public function test_create_skips_malformed_lines(): void
    {
        $metrics = [
            [EvaluationMetric::FIELD_SCORER_TYPE => 'cg'],
        ];

        $transformerStrings = [
            'binary_graded' => "0: 0\nbadline\n1: 3\n\n",
        ];

        $result = Transformers::create($metrics, BinaryScale::SCALE_TYPE, $transformerStrings);

        $rules = $result->getRules();
        $this->assertCount(2, $rules[GradedScale::SCALE_TYPE]);
    }

    public function test_create_skips_duplicate_lines(): void
    {
        $metrics = [
            [EvaluationMetric::FIELD_SCORER_TYPE => 'cg'],
        ];

        $transformerStrings = [
            'binary_graded' => "0: 0\n0: 0\n1: 3",
        ];

        $result = Transformers::create($metrics, BinaryScale::SCALE_TYPE, $transformerStrings);

        $rules = $result->getRules();
        // Duplicate "0: 0" should only count once
        $this->assertCount(2, $rules[GradedScale::SCALE_TYPE]);
    }

    public function test_to_form_array_roundtrip_with_create(): void
    {
        $original = new Transformers(BinaryScale::SCALE_TYPE, [
            GradedScale::SCALE_TYPE => [
                BinaryScale::IRRELEVANT => GradedScale::POOR,
                BinaryScale::RELEVANT => GradedScale::PERFECT,
            ],
        ]);

        $formArray = $original->toFormArray();

        $metrics = [
            [EvaluationMetric::FIELD_SCORER_TYPE => 'cg'],
        ];

        $restored = Transformers::create($metrics, BinaryScale::SCALE_TYPE, $formArray);

        $this->assertTrue($original->equals($restored));
    }
}
