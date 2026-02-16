<?php

namespace Database\Factories;

use App\Models\SearchEndpoint;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchEndpoint>
 */
class SearchEndpointFactory extends Factory
{
    protected $model = SearchEndpoint::class;

    public function definition(): array
    {
        return [
            SearchEndpoint::FIELD_USER_ID => User::factory(),
            SearchEndpoint::FIELD_TEAM_ID => Team::factory(),
            SearchEndpoint::FIELD_TYPE => SearchEndpoint::TYPE_SEARCH_API,
            SearchEndpoint::FIELD_NAME => $this->faker->words(3, true),
            SearchEndpoint::FIELD_URL => $this->faker->url(),
            SearchEndpoint::FIELD_METHOD => $this->faker->randomElement(SearchEndpoint::VALID_METHODS),
            SearchEndpoint::FIELD_DESCRIPTION => $this->faker->sentence(),
            SearchEndpoint::FIELD_HEADERS => [],
            SearchEndpoint::FIELD_MAPPER_TYPE => SearchEndpoint::MAPPER_TYPE_DOT_ARRAY,
            SearchEndpoint::FIELD_MAPPER_CODE => "id: data.items.*.id\nname: data.items.*.title",
            SearchEndpoint::FIELD_SETTINGS => [],
        ];
    }

    public function archived(): static
    {
        return $this->state([
            SearchEndpoint::FIELD_ARCHIVED_AT => now(),
        ]);
    }
}
