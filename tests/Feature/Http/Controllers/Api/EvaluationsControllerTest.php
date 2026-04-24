<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\Judge;
use App\Models\KeywordMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\GradedScale;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EvaluationsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        // Ensure Team has API tokens trait capability by just using it
        // The controller expects Auth::user() to be Team for API guard based on its code usage ($team->user_id)

        $endpoint = SearchEndpoint::factory()->create([
            SearchEndpoint::FIELD_USER_ID => $user->id,
            SearchEndpoint::FIELD_TEAM_ID => $team->id,
        ]);

        $model = SearchModel::factory()->create([
            SearchModel::FIELD_USER_ID => $user->id,
            SearchModel::FIELD_TEAM_ID => $team->id,
            SearchModel::FIELD_ENDPOINT_ID => $endpoint->id,
        ]);

        return [$user, $team, $model];
    }

    private function authenticate(Team $team): void
    {
        Sanctum::actingAs($team, ['*'], 'sanctum');
        Auth::guard('api')->setUser($team);
    }

    public function test_index_returns_evaluations_list(): void
    {
        [$user, $team, $model] = $this->createSetup();

        SearchEvaluation::factory()->count(3)->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
        ]);

        $this->authenticate($team);

        $response = $this->getJson('/api/v1/evaluations');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'status',
                    'created_at',
                ]
            ])
            ->assertJsonCount(3);
    }

    public function test_index_is_protected(): void
    {
        $response = $this->getJson('/api/v1/evaluations');
        $response->assertUnauthorized();
    }

    public function test_show_returns_evaluation_details(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 1,
                SearchEvaluation::SETTING_SHOW_POSITION => true,
                SearchEvaluation::SETTING_REUSE_STRATEGY => SearchEvaluation::REUSE_STRATEGY_NONE,
                SearchEvaluation::SETTING_AUTO_RESTART => false,
                SearchEvaluation::SETTING_TRANSFORMERS => ['scale_type' => BinaryScale::SCALE_TYPE, 'rules' => []],
                SearchEvaluation::SETTING_SCORING_GUIDELINES => 'Binary guidelines',
            ],
        ]);

        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 5,
            EvaluationMetric::FIELD_VALUE => null,
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_KEYWORD => 'kettle',
        ]);

        KeywordMetric::create([
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            KeywordMetric::FIELD_EVALUATION_METRIC_ID => $metric->id,
            KeywordMetric::FIELD_VALUE => 0.75,
        ]);

        $tag = Tag::factory()->create([
            Tag::FIELD_TEAM_ID => $team->id,
            Tag::FIELD_NAME => 'API',
        ]);
        $evaluation->tags()->attach($tag->id);

        $this->authenticate($team);

        $response = $this->getJson("/api/v1/evaluations/{$evaluation->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'model_id',
                'scale_type',
                'status',
                'progress',
                'name',
                'description',
                'settings',
                'metrics' => [['scorer_type', 'num_results', 'value']],
                'tags' => [['id', 'name']],
                'keywords' => [['keyword', 'metrics' => [['scorer_type', 'num_results', 'value']]]],
                'created_at',
                'finished_at',
            ])
            ->assertJsonPath('id', $evaluation->id)
            ->assertJsonPath('model_id', $model->id)
            ->assertJsonPath('scale_type', BinaryScale::SCALE_TYPE)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('settings.strategy', 1)
            ->assertJsonPath('settings.position', true)
            ->assertJsonPath('settings.reuse', SearchEvaluation::REUSE_STRATEGY_NONE)
            ->assertJsonPath('settings.auto_restart', false)
            ->assertJsonPath('settings.transformers.scale_type', BinaryScale::SCALE_TYPE)
            ->assertJsonPath('settings.scoring_guidelines', 'Binary guidelines')
            ->assertJsonPath('metrics.0.scorer_type', 'precision')
            ->assertJsonPath('metrics.0.num_results', 5)
            ->assertJsonPath('metrics.0.value', null)
            ->assertJsonPath('tags.0.id', $tag->id)
            ->assertJsonPath('tags.0.name', 'API')
            ->assertJsonPath('keywords.0.keyword', $keyword->keyword)
            ->assertJsonPath('keywords.0.metrics.0.scorer_type', 'precision')
            ->assertJsonPath('keywords.0.metrics.0.num_results', 5)
            ->assertJsonPath('keywords.0.metrics.0.value', 0.75);
    }

    public function test_store_creates_evaluation(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $tag = Tag::factory()->create([
            Tag::FIELD_TEAM_ID => $team->id,
            Tag::FIELD_NAME => 'API',
        ]);

        $data = [
            'model_id' => $model->id,
            'name' => 'New Evaluation',
            'description' => 'Evaluation description via API',
            'scale_type' => BinaryScale::SCALE_TYPE,
            'keywords' => ['keyword1', 'keyword2'],
            'metrics' => [
                ['scorer_type' => 'precision', 'num_results' => 5],
                ['scorer_type' => 'ap', 'num_results' => 10],
            ],
            'transformers' => ['scale_type' => BinaryScale::SCALE_TYPE, 'rules' => []],
            'tags' => [['id' => $tag->id]],
            'setting_feedback_strategy' => 1,
            'setting_show_position' => true,
            'setting_auto_restart' => false,
            'setting_reuse_strategy' => SearchEvaluation::REUSE_STRATEGY_NONE,
            'setting_scoring_guidelines' => 'Use binary relevance only',
        ];

        $this->authenticate($team);

        $response = $this->postJson('/api/v1/evaluations', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'model_id',
                'scale_type',
                'status',
                'progress',
                'name',
                'description',
                'settings',
                'metrics' => [['scorer_type', 'num_results', 'value']],
                'tags' => [['id', 'name']],
                'keywords' => [['keyword', 'metrics']],
                'created_at',
                'finished_at',
            ])
            ->assertJsonPath('name', 'New Evaluation')
            ->assertJsonPath('description', 'Evaluation description via API')
            ->assertJsonPath('model_id', $model->id)
            ->assertJsonPath('scale_type', BinaryScale::SCALE_TYPE)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('progress', 0)
            ->assertJsonPath('settings.strategy', 1)
            ->assertJsonPath('settings.position', true)
            ->assertJsonPath('settings.reuse', SearchEvaluation::REUSE_STRATEGY_NONE)
            ->assertJsonPath('settings.auto_restart', false)
            ->assertJsonPath('settings.transformers.scale_type', BinaryScale::SCALE_TYPE)
            ->assertJsonPath('settings.scoring_guidelines', 'Use binary relevance only')
            ->assertJsonPath('metrics.0.scorer_type', 'precision')
            ->assertJsonPath('metrics.0.num_results', 5)
            ->assertJsonPath('metrics.0.value', null)
            ->assertJsonPath('metrics.1.scorer_type', 'ap')
            ->assertJsonPath('metrics.1.num_results', 10)
            ->assertJsonPath('metrics.1.value', null)
            ->assertJsonPath('tags.0.id', $tag->id)
            ->assertJsonPath('tags.0.name', 'API')
            ->assertJsonPath('keywords.0.keyword', 'keyword1')
            ->assertJsonPath('keywords.0.metrics.0.scorer_type', 'precision')
            ->assertJsonPath('keywords.0.metrics.0.num_results', 5)
            ->assertJsonPath('keywords.0.metrics.0.value', null)
            ->assertJsonPath('keywords.0.metrics.1.scorer_type', 'ap')
            ->assertJsonPath('keywords.0.metrics.1.num_results', 10)
            ->assertJsonPath('keywords.0.metrics.1.value', null)
            ->assertJsonPath('keywords.1.keyword', 'keyword2')
            ->assertJsonPath('keywords.1.metrics.0.value', null)
            ->assertJsonPath('keywords.1.metrics.1.value', null);

        $this->assertDatabaseHas('search_evaluations', [
            'name' => 'New Evaluation',
            'model_id' => $model->id,
            'description' => 'Evaluation description via API',
            'scale_type' => BinaryScale::SCALE_TYPE,
            'status' => SearchEvaluation::STATUS_PENDING,
            'progress' => 0,
        ]);

        $evaluation = SearchEvaluation::query()->where('name', 'New Evaluation')->firstOrFail();

        $this->assertSame($user->id, $evaluation->user_id);
        $this->assertSame(1, $evaluation->getFeedbackStrategy());
        $this->assertTrue($evaluation->showPosition());
        $this->assertSame(SearchEvaluation::REUSE_STRATEGY_NONE, $evaluation->getReuseStrategy());
        $this->assertFalse($evaluation->autoRestart());
        $this->assertSame('Use binary relevance only', $evaluation->getScoringGuidelines());
        $this->assertSame(BinaryScale::SCALE_TYPE, $evaluation->getTransformers()->getScaleType());
        $this->assertSame([], $evaluation->getTransformers()->getRules());

        $this->assertCount(2, $evaluation->metrics);
        $this->assertCount(2, $evaluation->keywords);
        $this->assertCount(1, $evaluation->tags);
        $this->assertSame(['keyword1', 'keyword2'], $evaluation->keywords->pluck(EvaluationKeyword::FIELD_KEYWORD)->all());
        $this->assertSame(['precision', 'ap'], $evaluation->metrics->pluck(EvaluationMetric::FIELD_SCORER_TYPE)->all());
        $this->assertSame([5, 10], $evaluation->metrics->pluck(EvaluationMetric::FIELD_NUM_RESULTS)->all());
        $this->assertSame([$tag->id], $evaluation->tags->pluck(Tag::FIELD_ID)->all());
    }

    public function test_store_validates_request(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $this->authenticate($team);

        $response = $this->postJson('/api/v1/evaluations', []); // Empty data

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'model_id', 
                'name',
                'scale_type', 
                'keywords', 
                'metrics',
                'transformers',
                'setting_feedback_strategy'
            ]);
    }

    public function test_finish_changes_status(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $this->authenticate($team);

        $response = $this->postJson("/api/v1/evaluations/{$evaluation->id}/finish");

        $response->assertOk(); 

        $evaluation->refresh();
        $this->assertTrue($evaluation->isFinished());
    }

    public function test_finish_fails_without_permission(): void
    {
        [$owner, $team, $model] = $this->createSetup();
        
        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $owner->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        // Create another team
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $this->authenticate($otherTeam);

        $response = $this->postJson("/api/v1/evaluations/{$evaluation->id}/finish");

        // Should return 404 because controller scopes query by team
        $response->assertNotFound();
    }

    public function test_start_dispatches_job(): void
    {
        [$user, $team, $model] = $this->createSetup();
        \Illuminate\Support\Facades\Queue::fake();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $this->authenticate($team);

        $response = $this->postJson("/api/v1/evaluations/{$evaluation->id}/start");

        $response->assertOk()
            ->assertJson(['status' => 'OK']);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\Evaluations\StartEvaluationJob::class, function ($job) use ($evaluation) {
            return $job->uniqueId() == $evaluation->id;
        });
    }

    public function test_stop_dispatches_job(): void
    {
        [$user, $team, $model] = $this->createSetup();
        \Illuminate\Support\Facades\Queue::fake();

        $evaluation = SearchEvaluation::factory()->active()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $this->authenticate($team);

        $response = $this->postJson("/api/v1/evaluations/{$evaluation->id}/stop");

        $response->assertOk()
            ->assertJson(['status' => 'OK']);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\Evaluations\PauseEvaluationJob::class, function ($job) use ($evaluation) {
            return $job->uniqueId() == $evaluation->id;
        });
    }

    public function test_delete_removes_evaluation(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $this->authenticate($team);

        $response = $this->deleteJson("/api/v1/evaluations/{$evaluation->id}");

        $response->assertOk()
            ->assertJson(['status' => 'OK']);

        $this->assertDatabaseMissing('search_evaluations', ['id' => $evaluation->id]);
    }

    public function test_judgements_returns_data_for_finished_evaluation(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        // Judgements require keywords and snapshots to actually return something meaningful via service
        // But the controller just calls the service. Using an empty finished evaluation should return empty array or handle gracefully.
        // Let's expect empty array for basics.

        $this->authenticate($team);

        $response = $this->getJson("/api/v1/evaluations/{$evaluation->id}/judgements");

        $response->assertOk()
            ->assertJson([]); // Expecting empty array if no data
    }

    public function test_judgements_aggregates_human_and_ai_feedback_for_snapshot(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->finished()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
            SearchEvaluation::FIELD_SCALE_TYPE => BinaryScale::SCALE_TYPE,
            SearchEvaluation::FIELD_SETTINGS => [
                SearchEvaluation::SETTING_FEEDBACK_STRATEGY => 3,
            ],
        ]);

        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_KEYWORD => 'kettle',
        ]);

        $snapshot = SearchSnapshot::factory()->create([
            SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            SearchSnapshot::FIELD_DOC_ID => 'doc-1',
            SearchSnapshot::FIELD_POSITION => 1,
        ]);

        $judge = Judge::factory()->create([
            Judge::FIELD_USER_ID => $user->id,
            Judge::FIELD_TEAM_ID => $team->id,
        ]);

        // Majority relevant across human + AI should produce grade=1 for binary scale
        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_JUDGE_ID => null,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
        ]);

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_JUDGE_ID => $judge->id,
            UserFeedback::FIELD_GRADE => BinaryScale::RELEVANT,
            UserFeedback::FIELD_REASON => 'AI says relevant',
        ]);

        UserFeedback::factory()->create([
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $snapshot->id,
            UserFeedback::FIELD_USER_ID => $user->id,
            UserFeedback::FIELD_JUDGE_ID => null,
            UserFeedback::FIELD_GRADE => BinaryScale::IRRELEVANT,
        ]);

        $this->authenticate($team);

        $response = $this->getJson("/api/v1/evaluations/{$evaluation->id}/judgements");

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'grade' => BinaryScale::RELEVANT,
                'keyword' => 'kettle',
                'position' => 1,
                'doc' => 'doc-1',
            ]);
    }

    public function test_show_includes_per_keyword_metrics(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $precision = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_VALUE => 0.85,
        ]);

        $ndcg = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'ndcg',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
            EvaluationMetric::FIELD_VALUE => 0.92,
        ]);

        $kw1 = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_KEYWORD => 'freidora',
        ]);

        $kw2 = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationKeyword::FIELD_KEYWORD => 'microondas',
        ]);

        // kw1: both values present
        KeywordMetric::create([
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $kw1->id,
            KeywordMetric::FIELD_EVALUATION_METRIC_ID => $precision->id,
            KeywordMetric::FIELD_VALUE => 0.954,
        ]);
        KeywordMetric::create([
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $kw1->id,
            KeywordMetric::FIELD_EVALUATION_METRIC_ID => $ndcg->id,
            KeywordMetric::FIELD_VALUE => 0.971,
        ]);

        // kw2: only precision; ndcg missing → expected null in response
        KeywordMetric::create([
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $kw2->id,
            KeywordMetric::FIELD_EVALUATION_METRIC_ID => $precision->id,
            KeywordMetric::FIELD_VALUE => 0.5,
        ]);

        $this->authenticate($team);

        $response = $this->getJson("/api/v1/evaluations/{$evaluation->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'keywords')
            ->assertJsonPath('keywords.0.keyword', 'freidora')
            ->assertJsonCount(2, 'keywords.0.metrics')
            ->assertJsonPath('keywords.0.metrics.0.scorer_type', 'precision')
            ->assertJsonPath('keywords.0.metrics.0.num_results', 10)
            ->assertJsonPath('keywords.0.metrics.0.value', 0.95)
            ->assertJsonPath('keywords.0.metrics.1.scorer_type', 'ndcg')
            ->assertJsonPath('keywords.0.metrics.1.num_results', 10)
            ->assertJsonPath('keywords.0.metrics.1.value', 0.97)
            ->assertJsonPath('keywords.1.keyword', 'microondas')
            ->assertJsonCount(2, 'keywords.1.metrics')
            ->assertJsonPath('keywords.1.metrics.0.scorer_type', 'precision')
            ->assertJsonPath('keywords.1.metrics.0.value', 0.5)
            ->assertJsonPath('keywords.1.metrics.1.scorer_type', 'ndcg')
            ->assertJsonPath('keywords.1.metrics.1.value', null);
    }

    public function test_show_does_not_have_n_plus_one_for_keyword_metrics(): void
    {
        [$user, $team, $model] = $this->createSetup();

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_USER_ID => $user->id,
            SearchEvaluation::FIELD_MODEL_ID => $model->id,
        ]);

        $metrics = collect(['precision', 'ap', 'ndcg'])->map(fn (string $type) =>
            EvaluationMetric::factory()->create([
                EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
                EvaluationMetric::FIELD_SCORER_TYPE => $type,
                EvaluationMetric::FIELD_NUM_RESULTS => 10,
            ])
        );

        for ($i = 0; $i < 8; $i++) {
            $keyword = EvaluationKeyword::factory()->create([
                EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
                EvaluationKeyword::FIELD_KEYWORD => "kw-{$i}",
            ]);

            foreach ($metrics as $metric) {
                KeywordMetric::create([
                    KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
                    KeywordMetric::FIELD_EVALUATION_METRIC_ID => $metric->id,
                    KeywordMetric::FIELD_VALUE => 0.5,
                ]);
            }
        }

        $this->authenticate($team);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->getJson("/api/v1/evaluations/{$evaluation->id}");

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk()
            ->assertJsonCount(8, 'keywords');

        $this->assertLessThan(8, count($queries), sprintf(
            'Expected query count to not scale with keyword count, got %d:%s%s',
            count($queries),
            PHP_EOL,
            collect($queries)->pluck('query')->implode(PHP_EOL),
        ));
    }
}
