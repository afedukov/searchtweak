<?php

namespace Tests\Feature\Services\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Evaluations\JudgementsService;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JudgementsServiceTest extends TestCase
{
    use RefreshDatabase;

    private JudgementsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new JudgementsService();
    }

    private function createEvaluationWithData(): SearchEvaluation
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

        return SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
            ],
        ]);
    }

    public function test_process_calls_callback_for_graded_snapshots(): void
    {
        $evaluation = $this->createEvaluationWithData();
        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_KEYWORD => 'laptop',
        ]);

        // Create snapshot without triggering the booted() event
        $snapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-1',
            SearchSnapshot::FIELD_NAME => 'Test Doc',
            SearchSnapshot::FIELD_DOC => ['id' => 'doc-1'],
        ]);
        $snapshot->saveQuietly();

        $user = User::factory()->create();
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        $results = [];
        $this->service->process($evaluation, function ($grade, $kw, $docId, $position) use (&$results) {
            $results[] = compact('grade', 'kw', 'docId', 'position');
        });

        $this->assertCount(1, $results);
        $this->assertEquals(BinaryScale::RELEVANT, $results[0]['grade']);
        $this->assertEquals('laptop', $results[0]['kw']);
        $this->assertEquals('doc-1', $results[0]['docId']);
        $this->assertEquals(1, $results[0]['position']);
    }

    public function test_process_skips_ungraded_snapshots(): void
    {
        $evaluation = $this->createEvaluationWithData();
        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_KEYWORD => 'phone',
        ]);

        $snapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-2',
            SearchSnapshot::FIELD_NAME => 'Ungraded Doc',
            SearchSnapshot::FIELD_DOC => ['id' => 'doc-2'],
        ]);
        $snapshot->saveQuietly();

        // Create ungraded feedback
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);

        $results = [];
        $this->service->process($evaluation, function ($grade, $kw, $docId, $position) use (&$results) {
            $results[] = compact('grade', 'kw', 'docId', 'position');
        });

        $this->assertEmpty($results);
    }
}
