<?php

namespace Tests\Feature\Actions\Users;

use App\Actions\Users\DeleteTag;
use App\Models\EvaluationTag;
use App\Models\SearchEvaluation;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTagTest extends TestCase
{
    use RefreshDatabase;

    private DeleteTag $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new DeleteTag();
    }

    private function createTeamOwner(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);
        $this->actingAs($user);

        return [$user, $user->currentTeam];
    }

    public function test_delete_tag_successfully(): void
    {
        [$user, $team] = $this->createTeamOwner();

        $tag = Tag::factory()->create([
            Tag::FIELD_TEAM_ID => $team->id,
        ]);

        $this->action->delete($team, $tag->id);

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_delete_tag_throws_when_tag_not_found(): void
    {
        [$user, $team] = $this->createTeamOwner();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag not found.');

        $this->action->delete($team, 99999);
    }

    public function test_delete_tag_throws_when_assigned_to_user(): void
    {
        [$user, $team] = $this->createTeamOwner();

        $tag = Tag::factory()->create([
            Tag::FIELD_TEAM_ID => $team->id,
        ]);

        UserTag::create([
            UserTag::FIELD_USER_ID => $user->id,
            UserTag::FIELD_TAG_ID => $tag->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag is assigned to a user.');

        $this->action->delete($team, $tag->id);

        // Verify tag was NOT deleted
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_delete_tag_throws_when_assigned_to_evaluation(): void
    {
        [$user, $team] = $this->createTeamOwner();

        $tag = Tag::factory()->create([
            Tag::FIELD_TEAM_ID => $team->id,
        ]);

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
        ]);

        EvaluationTag::create([
            EvaluationTag::FIELD_EVALUATION_ID => $evaluation->id,
            EvaluationTag::FIELD_TAG_ID => $tag->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag is assigned to an evaluation.');

        $this->action->delete($team, $tag->id);

        // Verify tag was NOT deleted
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_delete_tag_from_another_team_not_found(): void
    {
        [$user, $team] = $this->createTeamOwner();

        // Create a tag belonging to a different team
        $tag = Tag::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag not found.');

        $this->action->delete($team, $tag->id);
    }
}
