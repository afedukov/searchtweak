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
use App\Services\Scorers\Scales\GradedScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecalculateMetricsStrategyThreeTest extends TestCase
{
    use RefreshDatabase;

    private RecalculateMetrics $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new RecalculateMetrics();
        Queue::fake();
    }

    private function createEvaluation(string $scaleType = BinaryScale::SCALE_TYPE): SearchEvaluation
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

        return SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => $scaleType,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 3,
            ],
        ]);
    }

    private function createSnapshot(EvaluationKeyword $keyword, int $position, string $docId): SearchSnapshot
    {
        return SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_POSITION => $position,
            SearchSnapshot::FIELD_DOC_ID => $docId,
        ]);
    }

    /**
     * @param array<int|null> $grades
     */
    private function setSnapshotGrades(SearchSnapshot $snapshot, array $grades): void
    {
        $feedbacks = UserFeedback::query()
            ->where(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshot->id)
            ->orderBy(UserFeedback::FIELD_ID)
            ->get();

        $this->assertCount(3, $feedbacks);
        $this->assertCount(3, $grades);

        foreach ($feedbacks as $index => $feedback) {
            $feedback->grade = $grades[$index];
            $feedback->saveQuietly();
        }
    }

    public function test_precision_uses_majority_value_per_snapshot_for_strategy_three(): void
    {
        $evaluation = $this->createEvaluation(BinaryScale::SCALE_TYPE);

        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 3,
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $s1 = $this->createSnapshot($keyword, 1, 'doc-1');
        $s2 = $this->createSnapshot($keyword, 2, 'doc-2');
        $s3 = $this->createSnapshot($keyword, 3, 'doc-3');

        $this->setSnapshotGrades($s1, [1, 1, 0]); // majority relevant => 1
        $this->setSnapshotGrades($s2, [0, 0, 1]); // majority irrelevant => 0
        $this->setSnapshotGrades($s3, [1, 0, null]); // tie => null (excluded in precision)

        $this->action->recalculate($keyword);

        $keywordMetric = KeywordMetric::query()
            ->where(KeywordMetric::FIELD_EVALUATION_METRIC_ID, $metric->id)
            ->first();

        $this->assertNotNull($keywordMetric);
        // graded snapshots are first two => 1 relevant out of 2 graded
        $this->assertEquals(0.5, $keywordMetric->value);
    }

    public function test_ap_and_rr_handle_tie_as_non_relevant_position(): void
    {
        $evaluation = $this->createEvaluation(BinaryScale::SCALE_TYPE);

        $apMetric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'ap',
            EvaluationMetric::FIELD_NUM_RESULTS => 3,
        ]);
        $rrMetric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'rr',
            EvaluationMetric::FIELD_NUM_RESULTS => 3,
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $s1 = $this->createSnapshot($keyword, 1, 'doc-1');
        $s2 = $this->createSnapshot($keyword, 2, 'doc-2');
        $s3 = $this->createSnapshot($keyword, 3, 'doc-3');

        $this->setSnapshotGrades($s1, [1, 0, null]); // tie => null
        $this->setSnapshotGrades($s2, [1, 1, 0]); // relevant
        $this->setSnapshotGrades($s3, [0, 0, 1]); // irrelevant

        $this->action->recalculate($keyword);

        $apKeywordMetric = KeywordMetric::query()
            ->where(KeywordMetric::FIELD_EVALUATION_METRIC_ID, $apMetric->id)
            ->first();
        $rrKeywordMetric = KeywordMetric::query()
            ->where(KeywordMetric::FIELD_EVALUATION_METRIC_ID, $rrMetric->id)
            ->first();

        $this->assertNotNull($apKeywordMetric);
        $this->assertNotNull($rrKeywordMetric);

        // AP: only relevant at k=2 => precision@2 = 1/2 => AP = 0.5
        $this->assertEqualsWithDelta(0.5, $apKeywordMetric->value, 0.0001);
        // RR: first relevant at rank 2 => 1/2
        $this->assertEqualsWithDelta(0.5, $rrKeywordMetric->value, 0.0001);
    }

    public function test_graded_cg_dcg_ndcg_use_average_of_three_grades_per_snapshot(): void
    {
        $evaluation = $this->createEvaluation(GradedScale::SCALE_TYPE);

        $cgMetric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'cg',
            EvaluationMetric::FIELD_NUM_RESULTS => 3,
        ]);
        $dcgMetric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'dcg',
            EvaluationMetric::FIELD_NUM_RESULTS => 3,
        ]);
        $ndcgMetric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'ndcg',
            EvaluationMetric::FIELD_NUM_RESULTS => 3,
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $s1 = $this->createSnapshot($keyword, 1, 'doc-1');
        $s2 = $this->createSnapshot($keyword, 2, 'doc-2');
        $s3 = $this->createSnapshot($keyword, 3, 'doc-3');

        $this->setSnapshotGrades($s1, [0, 0, 0]); // avg 0
        $this->setSnapshotGrades($s2, [1, 1, 1]); // avg 1
        $this->setSnapshotGrades($s3, [3, 2, 1]); // avg 2

        $this->action->recalculate($keyword);

        $cg = KeywordMetric::query()->where(KeywordMetric::FIELD_EVALUATION_METRIC_ID, $cgMetric->id)->first();
        $dcg = KeywordMetric::query()->where(KeywordMetric::FIELD_EVALUATION_METRIC_ID, $dcgMetric->id)->first();
        $ndcg = KeywordMetric::query()->where(KeywordMetric::FIELD_EVALUATION_METRIC_ID, $ndcgMetric->id)->first();

        $this->assertNotNull($cg);
        $this->assertNotNull($dcg);
        $this->assertNotNull($ndcg);

        // CG = 0 + 1 + 2 = 3
        $this->assertEqualsWithDelta(3.0, $cg->value, 0.0001);
        // DCG = 0/log2(2) + 1/log2(3) + 2/log2(4) = 0 + 0.63093 + 1 = 1.63093
        $this->assertEqualsWithDelta(1.6309, $dcg->value, 0.001);
        // Ideal order [2,1,0] gives higher DCG => nDCG should be between 0 and 1
        $this->assertGreaterThan(0.0, $ndcg->value);
        $this->assertLessThan(1.0, $ndcg->value);
    }

    public function test_progress_is_counted_by_feedback_slots_for_strategy_three(): void
    {
        $evaluation = $this->createEvaluation(BinaryScale::SCALE_TYPE);

        EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 1,
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $snapshot = $this->createSnapshot($keyword, 1, 'doc-1');

        $this->setSnapshotGrades($snapshot, [1, 0, null]); // 2/3 slots graded

        $this->action->recalculate($keyword);
        $evaluation->refresh();

        $this->assertEqualsWithDelta(66.6667, $evaluation->progress, 0.001);
    }

    public function test_evaluation_metric_average_ignores_null_keyword_values_with_strategy_three(): void
    {
        $evaluation = $this->createEvaluation(BinaryScale::SCALE_TYPE);

        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 1,
        ]);

        $keyword1 = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);
        $keyword2 = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $s1 = $this->createSnapshot($keyword1, 1, 'doc-k1');
        $s2 = $this->createSnapshot($keyword2, 1, 'doc-k2');

        $this->setSnapshotGrades($s1, [1, 0, null]); // tie => null keyword metric
        $this->setSnapshotGrades($s2, [1, 1, 0]); // relevant => precision 1

        $this->action->recalculate($keyword1);
        $this->action->recalculate($keyword2);

        $metric->refresh();
        // avg only non-null keyword metric values => 1.0
        $this->assertEqualsWithDelta(1.0, $metric->value, 0.0001);
    }
}
