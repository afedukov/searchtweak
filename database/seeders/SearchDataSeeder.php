<?php

namespace Database\Seeders;

use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class SearchDataSeeder extends Seeder
{
    /**
     * Seed the application's database with development endpoint, model and keywords.
     */
    public function run(): void
    {
        $user = User::where(User::FIELD_EMAIL, 'admin@searchtweak.com')->firstOrFail();
        $teamId = $user->currentTeam->id;

        // Endpoint: Metro Markets
        $endpoint = SearchEndpoint::create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $teamId,
            SearchEndpoint::FIELD_TYPE => SearchEndpoint::TYPE_SEARCH_API,
            SearchEndpoint::FIELD_NAME => 'Metro Markets',
            SearchEndpoint::FIELD_DESCRIPTION => 'Metro Marketplace search API (Germany)',
            SearchEndpoint::FIELD_URL => 'https://app-search-2.prod.de.metro-marketplace.cloud/api/v3/search',
            SearchEndpoint::FIELD_METHOD => 'GET',
            SearchEndpoint::FIELD_HEADERS => [
                'Country-Code' => 'de',
                'Accept-Language' => 'de',
            ],
            SearchEndpoint::FIELD_MAPPER_TYPE => SearchEndpoint::MAPPER_TYPE_DOT_ARRAY,
            SearchEndpoint::FIELD_MAPPER_CODE => implode("\n", [
                'id: data.items.*.id',
                'name: data.items.*.name',
                'image: data.items.*.image',
                'price: data.items.*.bestOffer.price.grossPrice',
                'url: "https://www.metro.de/marktplatz/product/" ~ data.items.*.id',
            ]),
            SearchEndpoint::FIELD_SETTINGS => [],
        ]);

        // Search Model: Baseline Search
        SearchModel::create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $teamId,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
            SearchModel::FIELD_NAME => 'Baseline Search',
            SearchModel::FIELD_DESCRIPTION => '',
            SearchModel::FIELD_HEADERS => [],
            SearchModel::FIELD_PARAMS => [
                'filter[top][phrase]' => '#query#',
            ],
            SearchModel::FIELD_BODY => '',
            SearchModel::FIELD_BODY_TYPE => SearchModel::BODY_TYPE_JSON,
            SearchModel::FIELD_SETTINGS => [
                SearchModel::SETTING_KEYWORDS => ['kühlschrank', 'teller', 'apple', 'kitchenaid'],
            ],
        ]);
    }
}
