<?php

namespace Tests\Feature\Services\Models;

use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use App\Models\Team;
use App\Models\User;
use App\Services\Mapper\MapperFactory;
use App\Services\Models\ExecuteModelService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecuteModelServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createModelWithEndpoint(array $endpointOverrides = [], array $modelOverrides = []): SearchModel
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        $endpoint = SearchEndpoint::factory()->create(array_merge([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
            SearchEndpoint::FIELD_URL => 'https://api.example.com/search',
            SearchEndpoint::FIELD_METHOD => 'GET',
            SearchEndpoint::FIELD_MAPPER_CODE => "id: data.results.*.id\nname: data.results.*.title",
        ], $endpointOverrides));

        return SearchModel::factory()->create(array_merge([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $team->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
            SearchModel::FIELD_PARAMS => ['q' => '#query#'],
            SearchModel::FIELD_BODY => '',
        ], $modelOverrides));
    }

    private function createServiceWithMock(array $responses): ExecuteModelService
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        return new ExecuteModelService(new MapperFactory(), $client);
    }

    public function test_execute_successful_response(): void
    {
        $model = $this->createModelWithEndpoint();

        $responseBody = json_encode([
            'results' => [
                ['id' => '1', 'title' => 'Laptop Pro'],
                ['id' => '2', 'title' => 'Laptop Air'],
            ],
        ]);

        $service = $this->createServiceWithMock([
            new Response(200, [], $responseBody),
        ]);

        $result = $service->initialize($model)->setLimit(10)->execute('laptop');

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(200, $result->getCode());
        $this->assertCount(2, $result->getDocuments());
        $this->assertEquals('1', $result->getDocuments()->first()->getId());
        $this->assertEquals('Laptop Pro', $result->getDocuments()->first()->getName());
    }

    public function test_execute_respects_limit(): void
    {
        $model = $this->createModelWithEndpoint();

        $items = array_map(fn ($i) => ['id' => (string) $i, 'title' => "Item $i"], range(1, 20));
        $responseBody = json_encode(['results' => $items]);

        $service = $this->createServiceWithMock([
            new Response(200, [], $responseBody),
        ]);

        $result = $service->initialize($model)->setLimit(5)->execute('test');

        $this->assertCount(5, $result->getDocuments());
    }

    public function test_execute_handles_http_error(): void
    {
        $model = $this->createModelWithEndpoint();

        $service = $this->createServiceWithMock([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $result = $service->initialize($model)->execute('laptop');

        // Guzzle doesn't throw for 500 by default without http_errors option
        // The result depends on mapper parsing the non-JSON body
        $this->assertEquals(500, $result->getCode());
    }

    public function test_execute_substitutes_query_in_params(): void
    {
        $model = $this->createModelWithEndpoint([], [
            SearchModel::FIELD_PARAMS => ['q' => '#query#', 'lang' => 'en'],
        ]);

        $responseBody = json_encode(['results' => [['id' => '1', 'title' => 'Test']]]);

        $service = $this->createServiceWithMock([
            new Response(200, [], $responseBody),
        ]);

        $result = $service->initialize($model)->execute('laptop');

        $this->assertTrue($result->isSuccessful());
    }

    public function test_execute_substitutes_query_in_body(): void
    {
        $model = $this->createModelWithEndpoint([
            SearchEndpoint::FIELD_METHOD => 'POST',
        ], [
            SearchModel::FIELD_BODY => '{"query": "#query#"}',
            SearchModel::FIELD_BODY_TYPE => SearchModel::BODY_TYPE_JSON,
        ]);

        $responseBody = json_encode(['results' => [['id' => '1', 'title' => 'Result']]]);

        $service = $this->createServiceWithMock([
            new Response(200, [], $responseBody),
        ]);

        $result = $service->initialize($model)->execute('phone');

        $this->assertTrue($result->isSuccessful());
        $this->assertCount(1, $result->getDocuments());
    }

    public function test_execute_empty_results(): void
    {
        $model = $this->createModelWithEndpoint();

        $responseBody = json_encode(['results' => []]);

        $service = $this->createServiceWithMock([
            new Response(200, [], $responseBody),
        ]);

        $result = $service->initialize($model)->execute('nonexistent');

        $this->assertTrue($result->isSuccessful());
        $this->assertCount(0, $result->getDocuments());
        $this->assertEquals(0, $result->getTotalCount());
    }
}
