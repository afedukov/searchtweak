<?php

namespace Tests\Feature\Services;

use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\Tag;
use App\Models\User;
use App\Services\SyncTagsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncTagsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SyncTagsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SyncTagsService();
    }

    public function test_sync_tags_on_evaluation(): void
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
        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $tag1 = Tag::factory()->create([Tag::FIELD_TEAM_ID => $team->id]);
        $tag2 = Tag::factory()->create([Tag::FIELD_TEAM_ID => $team->id]);

        $this->service->syncTags($evaluation, [
            [Tag::FIELD_ID => $tag1->id],
            [Tag::FIELD_ID => $tag2->id],
        ]);

        $this->assertEquals(2, $evaluation->tags()->count());
    }

    public function test_sync_removes_unselected_tags(): void
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
        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $tag1 = Tag::factory()->create([Tag::FIELD_TEAM_ID => $team->id]);
        $tag2 = Tag::factory()->create([Tag::FIELD_TEAM_ID => $team->id]);

        $this->service->syncTags($evaluation, [
            [Tag::FIELD_ID => $tag1->id],
            [Tag::FIELD_ID => $tag2->id],
        ]);

        $this->assertEquals(2, $evaluation->tags()->count());

        // Remove tag2
        $this->service->syncTags($evaluation, [
            [Tag::FIELD_ID => $tag1->id],
        ]);

        $this->assertEquals(1, $evaluation->tags()->count());
        $this->assertEquals($tag1->id, $evaluation->tags()->first()->id);
    }

    public function test_sync_tags_on_model(): void
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

        $tag = Tag::factory()->create([Tag::FIELD_TEAM_ID => $team->id]);

        $this->service->syncTags($model, [
            [Tag::FIELD_ID => $tag->id],
        ]);

        $this->assertEquals(1, $model->tags()->count());
    }

    public function test_sync_empty_removes_all(): void
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

        $tag = Tag::factory()->create([Tag::FIELD_TEAM_ID => $team->id]);

        $this->service->syncTags($model, [[Tag::FIELD_ID => $tag->id]]);
        $this->assertEquals(1, $model->tags()->count());

        $this->service->syncTags($model, []);
        $this->assertEquals(0, $model->tags()->count());
    }
}
