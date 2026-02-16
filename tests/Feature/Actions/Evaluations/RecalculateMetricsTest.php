<?php

namespace Tests\Feature\Actions\Evaluations;

use App\Actions\Evaluations\RecalculateMetrics;
use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\KeywordMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Scorers\Scales\BinaryScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecalculateMetricsTest extends TestCase
{
    use RefreshDatabase;

    private RecalculateMetrics $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new RecalculateMetrics();
        Queue::fake();
    }

    private function createSetup(int $numResults = 10): array
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
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => $numResults,
        ]);

        return [$user, $evaluation, $metric];
    }

    public function test_recalculate_creates_keyword_metric(): void
    {
        [$user, $evaluation, $metric] = $this->createSetup(2);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        // 2 relevant docs out of 2
        for ($i = 1; $i <= 2; $i++) {
            $snapshot = new SearchSnapshot([
                SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
                SearchSnapshot::FIELD_POSITION => $i,
                SearchSnapshot::FIELD_DOC_ID => "doc-$i",
                SearchSnapshot::FIELD_NAME => "Doc $i",
                SearchSnapshot::FIELD_DOC => [],
            ]);
            $snapshot->saveQuietly();

            UserFeedback::factory()->create([
                UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
                UserFeedback::FIELD_USER_ID => $user->id,
                UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            ]);
        }

        $this->action->recalculate($keyword);

        // Keyword metric should exist
        $keywordMetric = KeywordMetric::query()
            ->where(KeywordMetric::FIELD_EVALUATION_METRIC_ID, $metric->id)
            ->first();

        $this->assertNotNull($keywordMetric);
        // Precision@2 = 2/2 = 1.0
        $this->assertEquals(1.0, $keywordMetric->value);
    }

    public function test_recalculate_updates_evaluation_metric_value(): void
    {
        [$user, $evaluation, $metric] = $this->createSetup(2);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        // 1 relevant + 1 irrelevant
        $s1 = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-1',
            SearchSnapshot::FIELD_NAME => 'Doc 1',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $s1->saveQuietly();

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $s1->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        $s2 = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_POSITION => 2,
            SearchSnapshot::FIELD_DOC_ID => 'doc-2',
            SearchSnapshot::FIELD_NAME => 'Doc 2',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $s2->saveQuietly();

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $s2->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);

        $this->action->recalculate($keyword);

        $metric->refresh();
        // Precision@2 = 1/2 = 0.5
        $this->assertEquals(0.5, $metric->value);
    }

    public function test_recalculate_updates_evaluation_progress(): void
    {
        [$user, $evaluation, $metric] = $this->createSetup(1);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $snapshot = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-1',
            SearchSnapshot::FIELD_NAME => 'Doc 1',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $snapshot->saveQuietly();

        // Create feedback with grade (100% progress)
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        $this->action->recalculate($keyword);

        $evaluation->refresh();
        $this->assertEquals(100, $evaluation->progress);
    }

    public function test_recalculate_averages_across_keywords(): void
    {
        [$user, $evaluation, $metric] = $this->createSetup(1);

        // Keyword 1: precision = 1.0
        $keyword1 = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $s1 = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword1->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-1',
            SearchSnapshot::FIELD_NAME => 'Doc 1',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $s1->saveQuietly();

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $s1->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        // Keyword 2: precision = 0.0
        $keyword2 = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $s2 = new SearchSnapshot([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword2->id,
            SearchSnapshot::FIELD_POSITION => 1,
            SearchSnapshot::FIELD_DOC_ID => 'doc-2',
            SearchSnapshot::FIELD_NAME => 'Doc 2',
            SearchSnapshot::FIELD_DOC => [],
        ]);
        $s2->saveQuietly();

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $s2->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);

        // Recalculate for both keywords
        $this->action->recalculate($keyword1);
        $this->action->recalculate($keyword2);

        $metric->refresh();
        // Average of 1.0 and 0.0 = 0.5
        $this->assertEquals(0.5, $metric->value);
    }
}
