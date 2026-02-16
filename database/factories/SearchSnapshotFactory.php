<?php

namespace Database\Factories;

use App\Models\EvaluationKeyword;
use App\Models\SearchSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchSnapshot>
 */
class SearchSnapshotFactory extends Factory
{
    protected $model = SearchSnapshot::class;

    public function definition(): array
    {
        return [
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => EvaluationKeyword::factory(),
            SearchSnapshot::FIELD_POSITION => $this->faker->numberBetween(1, 10),
            SearchSnapshot::FIELD_DOC_ID => (string) $this->faker->unique()->randomNumber(6),
            SearchSnapshot::FIELD_NAME => $this->faker->words(3, true),
            SearchSnapshot::FIELD_IMAGE => $this->faker->optional()->imageUrl(),
            SearchSnapshot::FIELD_DOC => [
                'id' => $this->faker->randomNumber(6),
                'title' => $this->faker->sentence(),
            ],
        ];
    }
}
