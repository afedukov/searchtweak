<?php

namespace Database\Factories;

use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationKeyword>
 */
class EvaluationKeywordFactory extends Factory
{
    protected $model = EvaluationKeyword::class;

    public function definition(): array
    {
        return [
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => SearchEvaluation::factory(),
            EvaluationKeyword::FIELD_KEYWORD => $this->faker->words(2, true),
            EvaluationKeyword::FIELD_TOTAL_COUNT => EvaluationKeyword::TOTAL_COUNT_UNKNOWN,
            EvaluationKeyword::FIELD_EXECUTION_CODE => 200,
            EvaluationKeyword::FIELD_EXECUTION_MESSAGE => 'OK',
            EvaluationKeyword::FIELD_FAILED => false,
        ];
    }

    public function failed(): static
    {
        return $this->state([
            EvaluationKeyword::FIELD_EXECUTION_CODE => 500,
            EvaluationKeyword::FIELD_EXECUTION_MESSAGE => 'Internal Server Error',
            EvaluationKeyword::FIELD_FAILED => true,
        ]);
    }
}
