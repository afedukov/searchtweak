<?php

namespace Database\Factories;

use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\User;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchEvaluation>
 */
class SearchEvaluationFactory extends Factory
{
    protected $model = SearchEvaluation::class;

    public function definition(): array
    {
        return [
            SearchEvaluation::FIELD_USER_ID => User::factory(),
            SearchEvaluation::FIELD_MODEL_ID => SearchModel::factory(),
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING,
            SearchEvaluation::FIELD_PROGRESS => 0,
            SearchEvaluation::FIELD_NAME => $this->faker->words(3, true),
            SearchEvaluation::FIELD_DESCRIPTION => $this->faker->sentence(),
            SearchEvaluation::FIELD_SETTINGS => [],
            SearchEvaluation::FIELD_MAX_NUM_RESULTS => null,
            SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => 0,
            SearchEvaluation::FIELD_FAILED_KEYWORDS => 0,
            SearchEvaluation::FIELD_ARCHIVED => false,
            SearchEvaluation::FIELD_PINNED => false,
        ];
    }

    public function active(): static
    {
        return $this->state([
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_ACTIVE,
            SearchEvaluation::FIELD_MAX_NUM_RESULTS => 10,
        ]);
    }

    public function finished(): static
    {
        return $this->state([
            SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_FINISHED,
            SearchEvaluation::FIELD_MAX_NUM_RESULTS => 10,
            SearchEvaluation::FIELD_FINISHED_AT => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state([
            SearchEvaluation::FIELD_ARCHIVED => true,
        ]);
    }

    public function graded(): static
    {
        return $this->state([
            SearchEvaluation::FIELD_SCALE_TYPE => 'graded',
        ]);
    }

    public function detail(): static
    {
        return $this->state([
            SearchEvaluation::FIELD_SCALE_TYPE => 'detail',
        ]);
    }
}
