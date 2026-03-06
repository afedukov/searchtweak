<?php

namespace Database\Factories;

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationMetric>
 */
class EvaluationMetricFactory extends Factory
{
    protected $model = EvaluationMetric::class;

    public function definition(): array
    {
        return [
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => SearchEvaluation::factory(),
            EvaluationMetric::FIELD_SCORER_TYPE => $this->faker->randomElement(['precision', 'ap', 'rr', 'cg', 'dcg', 'ndcg', 'err', 'err_018']),
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_VALUE => 0,
            EvaluationMetric::FIELD_PREVIOUS_VALUE => null,
            EvaluationMetric::FIELD_SETTINGS => [],
            EvaluationMetric::FIELD_FINISHED_AT => null,
        ];
    }

    public function finished(float $value = 0.75): static
    {
        return $this->state([
            EvaluationMetric::FIELD_VALUE => $value,
            EvaluationMetric::FIELD_FINISHED_AT => now(),
        ]);
    }
}
