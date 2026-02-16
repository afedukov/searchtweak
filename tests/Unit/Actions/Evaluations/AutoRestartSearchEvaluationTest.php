<?php

namespace Tests\Unit\Actions\Evaluations;

use App\Actions\Evaluations\AutoRestartSearchEvaluation;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AutoRestartSearchEvaluationTest extends TestCase
{
    private function callGenerateNewEvaluationName(string $name): string
    {
        $method = new ReflectionMethod(AutoRestartSearchEvaluation::class, 'generateNewEvaluationName');

        return $method->invoke(null, $name);
    }

    public function test_increments_space_separated_number(): void
    {
        $this->assertEquals('Evaluation 2', $this->callGenerateNewEvaluationName('Evaluation 1'));
    }

    public function test_increments_underscore_separated_number(): void
    {
        $this->assertEquals('Evaluation_2', $this->callGenerateNewEvaluationName('Evaluation_1'));
    }

    public function test_increments_dash_separated_number(): void
    {
        $this->assertEquals('Evaluation-2', $this->callGenerateNewEvaluationName('Evaluation-1'));
    }

    public function test_increments_large_number(): void
    {
        $this->assertEquals('Run 100', $this->callGenerateNewEvaluationName('Run 99'));
    }

    public function test_appends_one_to_name_without_number(): void
    {
        $this->assertEquals('My Evaluation 1', $this->callGenerateNewEvaluationName('My Evaluation'));
    }

    public function test_preserves_prefix_with_spaces(): void
    {
        $this->assertEquals('Search Test v 4', $this->callGenerateNewEvaluationName('Search Test v 3'));
    }

    public function test_handles_zero(): void
    {
        $this->assertEquals('Run 1', $this->callGenerateNewEvaluationName('Run 0'));
    }

    public function test_name_ending_with_number_no_separator(): void
    {
        // "test123" — no separator before digit, should get " 1" appended
        $this->assertEquals('test123 1', $this->callGenerateNewEvaluationName('test123'));
    }

    public function test_single_number_name(): void
    {
        // " 5" — prefix is empty, separator is space
        $this->assertEquals(' 6', $this->callGenerateNewEvaluationName(' 5'));
    }
}
