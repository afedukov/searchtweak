<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Judges\JudgePairsJudgedCount;
use App\Models\EvaluationKeyword;
use App\Models\Judge;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JudgePairsJudgedCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_only_graded_feedbacks_for_requested_team_and_judge(): void
    {
        [$owner, $snapshot] = $this->createSnapshotForTeam();
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $owner->id,
            Judge::FIELD_TEAM_ID => $owner->currentTeam->id,
        ]);

        // Counted
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => 3,
        ]);

        // Not counted: ungraded
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => null,
        ]);

        // Not counted: different judge
        $anotherJudge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $owner->id,
            Judge::FIELD_TEAM_ID => $owner->currentTeam->id,
        ]);

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_JUDGE_ID => $anotherJudge->id,
            UserFeedback::FIELD_GRADE => 3,
        ]);

        Livewire::actingAs($owner)
            ->test(JudgePairsJudgedCount::class, [
                'judgeId' => $judge->id,
                'teamId' => $owner->currentTeam->id,
            ])
            ->assertSee('1');
    }

    private function createSnapshotForTeam(): array
    {
        $user = User::factory()->withPersonalTeam()->create();

        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $user->currentTeam->id,
        ]);

        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $user->currentTeam->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $snapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
        ]);

        return [$user, $snapshot];
    }
}
