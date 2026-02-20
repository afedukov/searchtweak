<?php

namespace Tests\Feature\Services\Leaderboard;

use App\Models\EvaluationKeyword;
use App\Models\Judge;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Leaderboard\LeaderboardQueryGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardQueryGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private LeaderboardQueryGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new LeaderboardQueryGenerator();
    }

    private function createFeedbackForUser(User $user, int $teamId, int $count = 1): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $owner->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
        ]);
        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $owner->id,
            SearchModel::FIELD_TEAM_ID => $teamId,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);
        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $owner->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        for ($i = 0; $i < $count; $i++) {
            $snapshot = new SearchSnapshot([
                SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
                SearchSnapshot::FIELD_POSITION => $i + 1,
                SearchSnapshot::FIELD_DOC_ID => 'doc-' . $i,
                SearchSnapshot::FIELD_NAME => 'Doc ' . $i,
                SearchSnapshot::FIELD_DOC => [],
            ]);
            $snapshot->saveQuietly();

            UserFeedback::factory()->create([
                UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
                UserFeedback::FIELD_USER_ID => $user->id,
                UserFeedback::FIELD_GRADE => 1,
            ]);
        }
    }

    private function createFeedbackForJudge(Judge $judge, int $teamId, int $count = 1): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $owner->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
        ]);
        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $owner->id,
            SearchModel::FIELD_TEAM_ID => $teamId,
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

        for ($i = 0; $i < $count; $i++) {
            $snapshot = new SearchSnapshot([
                SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
                SearchSnapshot::FIELD_POSITION => $i + 1,
                SearchSnapshot::FIELD_DOC_ID => 'doc-j-' . $i,
                SearchSnapshot::FIELD_NAME => 'Doc J ' . $i,
                SearchSnapshot::FIELD_DOC => [],
            ]);
            $snapshot->saveQuietly();

            UserFeedback::factory()->create([
                UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
                UserFeedback::FIELD_JUDGE_ID => $judge->id,
                UserFeedback::FIELD_GRADE => 1,
            ]);
        }
    }

    public function test_query_returns_results_for_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $this->createFeedbackForUser($user, $team->id, 3);

        $query = $this->generator->getQuery($team->id, [
            Carbon::now()->subYear(),
            Carbon::now()->addDay(),
        ]);

        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->user_id);
        $this->assertEquals(3, $results->first()->feedback_count);
    }

    public function test_get_dataset_format(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $this->createFeedbackForUser($user, $team->id, 2);

        $query = $this->generator->getQuery($team->id, [
            Carbon::now()->subYear(),
            Carbon::now()->addDay(),
        ]);

        $dataset = $this->generator->getDataset($query, 10);

        $this->assertCount(1, $dataset);
        $this->assertArrayHasKey('label', $dataset[0]);
        $this->assertArrayHasKey('value', $dataset[0]);
        $this->assertEquals($user->name, $dataset[0]['label']);
        $this->assertEquals(2, $dataset[0]['value']);
    }

    public function test_get_dataset_respects_limit(): void
    {
        $user1 = User::factory()->withPersonalTeam()->create();
        $user2 = User::factory()->create();
        $team = $user1->currentTeam;

        $this->createFeedbackForUser($user1, $team->id, 5);
        $this->createFeedbackForUser($user2, $team->id, 3);

        $query = $this->generator->getQuery($team->id, [
            Carbon::now()->subYear(),
            Carbon::now()->addDay(),
        ]);

        $dataset = $this->generator->getDataset($query, 1);

        $this->assertCount(1, $dataset);
    }

    public function test_get_judge_query_returns_results_for_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $owner->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);

        $this->createFeedbackForJudge($judge, $team->id, 4);

        $query = $this->generator->getJudgeQuery($team->id, [
            Carbon::now()->subYear(),
            Carbon::now()->addDay(),
        ]);

        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals($judge->id, $results->first()->judge_id);
        $this->assertEquals(4, $results->first()->feedback_count);
    }

    public function test_get_judge_dataset_format(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $owner->id,
            Judge::FIELD_TEAM_ID => $team->id,
            Judge::FIELD_NAME => 'Judge Dataset',
        ]);

        $this->createFeedbackForJudge($judge, $team->id, 2);

        $query = $this->generator->getJudgeQuery($team->id, [
            Carbon::now()->subYear(),
            Carbon::now()->addDay(),
        ]);

        $dataset = $this->generator->getJudgeDataset($query, 10);

        $this->assertCount(1, $dataset);
        $this->assertSame('Judge Dataset (AI)', $dataset[0]['label']);
        $this->assertEquals(2, $dataset[0]['value']);
    }
}
