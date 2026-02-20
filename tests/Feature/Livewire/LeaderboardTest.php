<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Leaderboard;
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

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_all_filter_shows_combined_entries_for_users_and_judges(): void
    {
        [$owner, $human, $judge] = $this->seedLeaderboardData();

        Livewire::actingAs($owner)
            ->test(Leaderboard::class)
            ->assertSet('filterType', Leaderboard::FILTER_TYPE_ALL)
            ->assertViewHas('showType', 'all')
            ->assertSee($human->name)
            ->assertSee($judge->name)
            ->assertViewHas('dataset', function (array $dataset) use ($human, $judge): bool {
                $labels = array_column($dataset, 'label');

                return in_array($human->name, $labels, true)
                    && in_array($judge->name . ' (AI)', $labels, true);
            });
    }

    public function test_users_filter_shows_only_human_entries_and_user_dataset(): void
    {
        [$owner, $human, $judge] = $this->seedLeaderboardData();

        Livewire::actingAs($owner)
            ->test(Leaderboard::class)
            ->set('filterType', Leaderboard::FILTER_TYPE_USERS)
            ->assertViewHas('showType', 'users')
            ->assertSee($human->name)
            ->assertDontSee($judge->name . ' (AI)')
            ->assertViewHas('dataset', function (array $dataset) use ($human, $judge): bool {
                $labels = array_column($dataset, 'label');

                return in_array($human->name, $labels, true)
                    && !in_array($judge->name . ' (AI)', $labels, true);
            });
    }

    public function test_judges_filter_shows_only_ai_entries_and_judge_dataset(): void
    {
        [$owner, $human, $judge] = $this->seedLeaderboardData();

        Livewire::actingAs($owner)
            ->test(Leaderboard::class)
            ->set('filterType', Leaderboard::FILTER_TYPE_JUDGES)
            ->assertViewHas('showType', 'judges')
            ->assertSee($judge->name)
            ->assertDontSee($human->name)
            ->assertViewHas('dataset', function (array $dataset) use ($human, $judge): bool {
                $labels = array_column($dataset, 'label');

                return in_array($judge->name . ' (AI)', $labels, true)
                    && !in_array($human->name, $labels, true);
            });
    }

    private function seedLeaderboardData(): array
    {
        $owner = User::factory()->withPersonalTeam()->create(['name' => 'Owner Leaderboard']);
        $human = User::factory()->create(['name' => 'Human Judge']);

        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $owner->id,
            SearchEndpoint::FIELD_TEAM_ID => $owner->currentTeam->id,
        ]);

        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $owner->id,
            SearchModel::FIELD_TEAM_ID => $owner->currentTeam->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $owner->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 3,
            ],
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $snapshotA = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
        ]);
        $snapshotB = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_POSITION => 2,
        ]);

        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $owner->id,
            Judge::FIELD_TEAM_ID => $owner->currentTeam->id,
            Judge::FIELD_NAME => 'AI Judge Alpha',
        ]);

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshotA->id,
            UserFeedback::FIELD_USER_ID => $human->id,
            UserFeedback::FIELD_JUDGE_ID => null,
            UserFeedback::FIELD_GRADE => 3,
        ]);

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshotB->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => 3,
        ]);

        return [$owner, $human, $judge];
    }
}
