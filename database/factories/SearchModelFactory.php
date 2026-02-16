<?php

namespace Database\Factories;

use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchModel>
 */
class SearchModelFactory extends Factory
{
    protected $model = SearchModel::class;

    public function definition(): array
    {
        return [
            SearchModel::FIELD_USER_ID => User::factory(),
            SearchModel::FIELD_TEAM_ID => Team::factory(),
            SearchModel::FIELD_ENDPOINT_ID => SearchEndpoint::factory(),
            SearchModel::FIELD_NAME => $this->faker->words(3, true),
            SearchModel::FIELD_DESCRIPTION => $this->faker->sentence(),
            SearchModel::FIELD_HEADERS => [],
            SearchModel::FIELD_PARAMS => ['q' => '#query#'],
            SearchModel::FIELD_BODY => '',
            SearchModel::FIELD_BODY_TYPE => SearchModel::BODY_TYPE_JSON,
            SearchModel::FIELD_SETTINGS => [],
        ];
    }
}
