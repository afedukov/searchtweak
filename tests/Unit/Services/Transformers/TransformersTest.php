<?php

namespace Tests\Unit\Services\Transformers;

use App\Services\Scorers\Scales\BinaryScale;
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
}
