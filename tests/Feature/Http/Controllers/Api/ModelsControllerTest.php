<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Tag;
use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use App\Models\Team;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ModelsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
        ]);

        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $team->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);

        return [$user, $team, $model];
    }

    private function authenticate(Team $team): void
    {
        Sanctum::actingAs($team, ['*'], 'sanctum');
        Auth::guard('api')->setUser($team);
    }

    public function test_index_returns_models_list(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $model->update([
            SearchModel::FIELD_NAME => 'Primary Model',
            SearchModel::FIELD_DESCRIPTION => 'Primary model description',
            SearchModel::FIELD_HEADERS => ['Accept-Language' => 'de'],
            SearchModel::FIELD_PARAMS => ['q' => '#query#'],
            SearchModel::FIELD_BODY => '{"query":"#query#"}',
            SearchModel::FIELD_BODY_TYPE => SearchModel::BODY_TYPE_JSON,
            SearchModel::FIELD_SETTINGS => [SearchModel::SETTING_KEYWORDS => ['kettle', 'toaster']],
        ]);
        $tag = Tag::factory()->create([
            Tag::FIELD_TEAM_ID => $team->id,
            Tag::FIELD_NAME => 'API',
        ]);
        $model->tags()->attach($tag->id);

        // Create another model to ensure list works
        $secondModel = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $team->id,
            SearchModel::FIELD_ENDPOINT_ID => $model->endpoint_id,
            SearchModel::FIELD_NAME => 'Secondary Model',
            SearchModel::FIELD_DESCRIPTION => 'Secondary model description',
            SearchModel::FIELD_HEADERS => ['X-Test' => '1'],
            SearchModel::FIELD_PARAMS => ['query' => '#query#'],
            SearchModel::FIELD_BODY => '',
            SearchModel::FIELD_BODY_TYPE => null,
            SearchModel::FIELD_SETTINGS => [],
        ]);

        $this->authenticate($team);

        $response = $this->getJson('/api/v1/models');

        $response->assertOk()
            ->assertJsonCount(2);

        $models = array_values($response->json());
        foreach ($models as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('endpoint', $item);
            $this->assertArrayHasKey('headers', $item);
            $this->assertArrayHasKey('params', $item);
            $this->assertArrayHasKey('body', $item);
            $this->assertArrayHasKey('body_type', $item);
            $this->assertArrayHasKey('settings', $item);
            $this->assertArrayHasKey('tags', $item);
            $this->assertArrayHasKey('created_at', $item);
            $this->assertArrayHasKey('id', $item['endpoint']);
            $this->assertArrayHasKey('name', $item['endpoint']);
            $this->assertArrayHasKey('method', $item['endpoint']);
            $this->assertArrayHasKey('url', $item['endpoint']);
        }

        $modelsById = collect($models)->keyBy('id');

        $this->assertSame('Primary Model', $modelsById[$model->id]['name']);
        $this->assertSame('Primary model description', $modelsById[$model->id]['description']);
        $this->assertSame($model->endpoint->id, $modelsById[$model->id]['endpoint']['id']);
        $this->assertSame($model->endpoint->name, $modelsById[$model->id]['endpoint']['name']);
        $this->assertSame($model->endpoint->method, $modelsById[$model->id]['endpoint']['method']);
        $this->assertSame($model->endpoint->url, $modelsById[$model->id]['endpoint']['url']);
        $this->assertSame(['Accept-Language' => 'de'], $modelsById[$model->id]['headers']);
        $this->assertSame(['q' => '#query#'], $modelsById[$model->id]['params']);
        $this->assertSame('{"query":"#query#"}', $modelsById[$model->id]['body']);
        $this->assertSame('JSON', $modelsById[$model->id]['body_type']);
        $this->assertSame([SearchModel::SETTING_KEYWORDS => ['kettle', 'toaster']], $modelsById[$model->id]['settings']);
        $this->assertSame($tag->id, $modelsById[$model->id]['tags'][0]['id']);
        $this->assertSame('API', $modelsById[$model->id]['tags'][0]['name']);

        $this->assertSame('Secondary Model', $modelsById[$secondModel->id]['name']);
        $this->assertSame('Secondary model description', $modelsById[$secondModel->id]['description']);
        $this->assertSame(['X-Test' => '1'], $modelsById[$secondModel->id]['headers']);
        $this->assertSame(['query' => '#query#'], $modelsById[$secondModel->id]['params']);
        $this->assertSame('', $modelsById[$secondModel->id]['body']);
        $this->assertNull($modelsById[$secondModel->id]['body_type']);
        $this->assertSame([], $modelsById[$secondModel->id]['settings']);
        $this->assertSame([], $modelsById[$secondModel->id]['tags']);
    }

    public function test_index_is_protected(): void
    {
        $response = $this->getJson('/api/v1/models');
        $response->assertUnauthorized();
    }

    public function test_show_returns_model_details(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $model->update([
            SearchModel::FIELD_NAME => 'Model API',
            SearchModel::FIELD_DESCRIPTION => 'Model API description',
            SearchModel::FIELD_HEADERS => ['Accept-Language' => 'en'],
            SearchModel::FIELD_PARAMS => ['q' => '#query#', 'page' => 1],
            SearchModel::FIELD_BODY => '{"query":"#query#","size":10}',
            SearchModel::FIELD_BODY_TYPE => SearchModel::BODY_TYPE_JSON,
            SearchModel::FIELD_SETTINGS => [SearchModel::SETTING_KEYWORDS => ['coffee machine']],
        ]);
        $tag = Tag::factory()->create([
            Tag::FIELD_TEAM_ID => $team->id,
            Tag::FIELD_NAME => 'Critical',
        ]);
        $model->tags()->attach($tag->id);

        $this->authenticate($team);

        $response = $this->getJson("/api/v1/models/{$model->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'endpoint' => ['id', 'name', 'method', 'url'],
                'headers',
                'params',
                'body',
                'body_type',
                'settings',
                'tags' => [['id', 'name']],
                'created_at',
            ])
            ->assertJsonPath('id', $model->id)
            ->assertJsonPath('name', 'Model API')
            ->assertJsonPath('description', 'Model API description')
            ->assertJsonPath('endpoint.id', $model->endpoint->id)
            ->assertJsonPath('endpoint.name', $model->endpoint->name)
            ->assertJsonPath('endpoint.method', $model->endpoint->method)
            ->assertJsonPath('endpoint.url', $model->endpoint->url)
            ->assertJsonPath('headers.Accept-Language', 'en')
            ->assertJsonPath('params.q', '#query#')
            ->assertJsonPath('params.page', 1)
            ->assertJsonPath('body', '{"query":"#query#","size":10}')
            ->assertJsonPath('body_type', 'JSON')
            ->assertJsonPath('settings.keywords.0', 'coffee machine')
            ->assertJsonPath('tags.0.id', $tag->id)
            ->assertJsonPath('tags.0.name', 'Critical');
    }

    public function test_show_fails_without_permission(): void
    {
        [$owner, $team, $model] = $this->createSetup();

        // Create another team
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $this->authenticate($otherTeam);

        $response = $this->getJson("/api/v1/models/{$model->id}");

        // Should return 404 because controller scopes query by team
        $response->assertNotFound();
    }
}
