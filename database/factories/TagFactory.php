<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            Tag::FIELD_TEAM_ID => Team::factory(),
            Tag::FIELD_NAME => $this->faker->unique()->word(),
            Tag::FIELD_COLOR => $this->faker->randomElement(array_keys(Tag::getColors())),
        ];
    }
}
