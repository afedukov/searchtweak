<?php

namespace Tests\Feature\Services\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use App\Models\Team;
use App\Models\User;
use App\Services\Evaluations\SyncKeywordsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncKeywordsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SyncKeywordsService $service;
    private SearchEvaluation $evaluation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SyncKeywordsService();

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
        $this->evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);
    }

    public function test_sync_from_string(): void
    {
        $this->service->syncString($this->evaluation, "laptop\nphone\ntablet");

        $keywords = $this->evaluation->keywords()->pluck(EvaluationKeyword::FIELD_KEYWORD)->all();

        $this->assertCount(3, $keywords);
        $this->assertContains('laptop', $keywords);
        $this->assertContains('phone', $keywords);
        $this->assertContains('tablet', $keywords);
    }

    public function test_sync_from_array(): void
    {
        $this->service->syncArray($this->evaluation, ['laptop', 'phone', 'tablet']);

        $this->assertEquals(3, $this->evaluation->keywords()->count());
    }

    public function test_sync_deduplicates(): void
    {
        $this->service->syncString($this->evaluation, "laptop\nlaptop\nphone");

        $this->assertEquals(2, $this->evaluation->keywords()->count());
    }

    public function test_sync_trims_whitespace(): void
    {
        $this->service->syncString($this->evaluation, "  laptop  \n  phone  ");

        $keywords = $this->evaluation->keywords()->pluck(EvaluationKeyword::FIELD_KEYWORD)->all();

        $this->assertContains('laptop', $keywords);
        $this->assertContains('phone', $keywords);
    }

    public function test_sync_removes_deleted_keywords(): void
    {
        $this->service->syncString($this->evaluation, "laptop\nphone");
        $this->assertEquals(2, $this->evaluation->keywords()->count());

        $this->service->syncString($this->evaluation, "laptop");
        $this->assertEquals(1, $this->evaluation->keywords()->count());
    }

    public function test_sync_adds_new_keywords(): void
    {
        $this->service->syncString($this->evaluation, "laptop");
        $this->assertEquals(1, $this->evaluation->keywords()->count());

        $this->service->syncString($this->evaluation, "laptop\nphone");
        $this->assertEquals(2, $this->evaluation->keywords()->count());
    }

    public function test_sync_filters_empty_lines(): void
    {
        $this->service->syncString($this->evaluation, "laptop\n\n\nphone\n");

        $this->assertEquals(2, $this->evaluation->keywords()->count());
    }

    public function test_validate_with_valid_string(): void
    {
        $team = Team::factory()->create();

        $this->service->validate("laptop\nphone", $team);

        $this->assertTrue(true); // No exception
    }

    public function test_validate_throws_on_empty(): void
    {
        $team = Team::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than 0');

        $this->service->validate('', $team);
    }

    public function test_validate_throws_on_too_many(): void
    {
        $team = Team::factory()->create();
        $keywords = implode("\n", array_map(fn ($i) => "keyword$i", range(1, 251)));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('250');

        $this->service->validate($keywords, $team);
    }

    public function test_validate_with_array(): void
    {
        $team = Team::factory()->create();

        $this->service->validate(['laptop', 'phone'], $team);

        $this->assertTrue(true); // No exception
    }

    public function test_get_keywords_from_string(): void
    {
        $result = SyncKeywordsService::getKeywordsFromString("laptop\nphone\n\nlaptop");

        $this->assertEquals(['laptop', 'phone'], $result->values()->all());
    }

    public function test_get_keywords_from_array(): void
    {
        $result = SyncKeywordsService::getKeywordsFromArray(['laptop', 'phone', '', 'laptop']);

        $this->assertEquals(['laptop', 'phone'], $result->values()->all());
    }
}
