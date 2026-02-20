<?php

namespace Database\Factories;

use App\Models\Judge;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Judge>
 */
class JudgeFactory extends Factory
{
    protected $model = Judge::class;

    public function definition(): array
    {
        return [
            Judge::FIELD_USER_ID => User::factory(),
            Judge::FIELD_TEAM_ID => Team::factory(),
            Judge::FIELD_NAME => $this->faker->words(3, true),
            Judge::FIELD_DESCRIPTION => $this->faker->sentence(),
            Judge::FIELD_PROVIDER => $this->faker->randomElement(Judge::VALID_PROVIDERS),
            Judge::FIELD_MODEL_NAME => $this->faker->randomElement(['gpt-4', 'claude-sonnet-4-5-20250929', 'gemini-pro']),
            Judge::FIELD_API_KEY => $this->faker->sha256(),
            Judge::FIELD_PROMPT_BINARY => $this->faker->paragraph(),
            Judge::FIELD_PROMPT_GRADED => $this->faker->paragraph(),
            Judge::FIELD_PROMPT_DETAIL => $this->faker->paragraph(),
            Judge::FIELD_SETTINGS => [],
        ];
    }

    public function archived(): static
    {
        return $this->state([
            Judge::FIELD_ARCHIVED_AT => now(),
        ]);
    }
}
