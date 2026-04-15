<?php

namespace App\Console\Commands;

use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\KeywordMetric;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Evaluations\ScoringGuidelinesService;
use App\Services\Scorers\Scales\ScaleFactory;
use App\Services\Scorers\ScorerFactory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedHeavyEvaluationCommand extends Command
{
    protected $signature = 'app:seed-heavy-evaluation
        {--model=2 : Target SearchModel id}
        {--keywords=150 : Number of keywords}
        {--snapshots=40 : Snapshots per keyword}
        {--feedbacks=3 : Feedback slots per snapshot}
        {--scale=graded : Scale type (binary|graded|detail)}';

    protected $description = 'Create a large evaluation for performance testing of the /evaluations/{id} page';

    public function handle(): int
    {
        $modelId       = (int) $this->option('model');
        $keywordsCount = (int) $this->option('keywords');
        $snapshotCount = (int) $this->option('snapshots');
        $feedbackSlots = max(1, (int) $this->option('feedbacks'));
        $scaleType     = (string) $this->option('scale');

        $model = SearchModel::find($modelId);
        if (!$model) {
            $this->error("SearchModel id={$modelId} not found");

            return self::FAILURE;
        }

        $user = User::where(User::FIELD_EMAIL, 'admin@searchtweak.com')->first()
            ?? User::orderBy(User::FIELD_ID)->firstOrFail();

        $this->info(sprintf(
            'Seeding heavy evaluation: model=%d keywords=%d snapshots=%d feedbackSlots=%d scale=%s',
            $modelId, $keywordsCount, $snapshotCount, $feedbackSlots, $scaleType
        ));

        $grades = ScaleFactory::create($scaleType)->getGrades();

        $evaluation = DB::transaction(function () use (
            $user, $model, $keywordsCount, $snapshotCount, $feedbackSlots, $scaleType, $grades
        ) {
            $evaluation = SearchEvaluation::create([
                SearchEvaluation::FIELD_USER_ID             => $user->id,
                SearchEvaluation::FIELD_MODEL_ID            => $model->id,
                SearchEvaluation::FIELD_SCALE_TYPE          => $scaleType,
                SearchEvaluation::FIELD_STATUS              => SearchEvaluation::STATUS_FINISHED,
                SearchEvaluation::FIELD_PROGRESS            => 100,
                SearchEvaluation::FIELD_NAME                => sprintf('Heavy Evaluation %dk x %ds', $keywordsCount, $snapshotCount),
                SearchEvaluation::FIELD_DESCRIPTION         => 'Synthetic evaluation for page-load performance testing',
                SearchEvaluation::FIELD_SETTINGS            => [
                    SearchEvaluation::SETTING_REUSE_STRATEGY     => SearchEvaluation::REUSE_STRATEGY_NONE,
                    SearchEvaluation::SETTING_SHOW_POSITION      => true,
                    SearchEvaluation::SETTING_FEEDBACK_STRATEGY  => $feedbackSlots,
                    SearchEvaluation::SETTING_AUTO_RESTART       => false,
                    SearchEvaluation::SETTING_TRANSFORMERS       => [
                        'scale_type' => $scaleType,
                        'rules'      => [],
                    ],
                    SearchEvaluation::SETTING_SCORING_GUIDELINES => app(ScoringGuidelinesService::class)
                        ->getDefaultScoringGuidelines()[$scaleType] ?? '',
                ],
                SearchEvaluation::FIELD_MAX_NUM_RESULTS      => $snapshotCount,
                SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS  => $keywordsCount,
                SearchEvaluation::FIELD_FAILED_KEYWORDS      => 0,
                SearchEvaluation::FIELD_ARCHIVED             => false,
                SearchEvaluation::FIELD_PINNED               => false,
                SearchEvaluation::FIELD_FINISHED_AT          => Carbon::now(),
            ]);

            // All scorer types at @10 + extras at @40
            $metricDefs = [];
            foreach (array_keys(ScorerFactory::SCORER_TYPES) as $type) {
                $metricDefs[] = ['scorer_type' => $type, 'num_results' => 10];
            }
            foreach (['precision', 'ap', 'dcg', 'ndcg'] as $type) {
                $metricDefs[] = ['scorer_type' => $type, 'num_results' => 40];
            }

            $metrics = [];
            foreach ($metricDefs as $def) {
                $metrics[] = EvaluationMetric::create([
                    EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
                    EvaluationMetric::FIELD_SCORER_TYPE          => $def['scorer_type'],
                    EvaluationMetric::FIELD_NUM_RESULTS          => $def['num_results'],
                    EvaluationMetric::FIELD_VALUE                => round(rand(30, 90) / 100, 4),
                    EvaluationMetric::FIELD_PREVIOUS_VALUE       => round(rand(30, 90) / 100, 4),
                    EvaluationMetric::FIELD_SETTINGS             => [],
                    EvaluationMetric::FIELD_FINISHED_AT          => Carbon::now(),
                ]);
            }

            $this->info(sprintf('  Created evaluation id=%d with %d metrics', $evaluation->id, count($metrics)));

            $this->seedKeywordsAndData(
                $evaluation,
                $metrics,
                $keywordsCount,
                $snapshotCount,
                $feedbackSlots,
                $grades,
                $user->id
            );

            return $evaluation;
        });

        $this->info(sprintf('Done. Evaluation id=%d — /evaluations/%d', $evaluation->id, $evaluation->id));

        return self::SUCCESS;
    }

    /**
     * @param array<int> $grades
     */
    private function seedKeywordsAndData(
        SearchEvaluation $evaluation,
        array $metrics,
        int $keywordsCount,
        int $snapshotCount,
        int $feedbackSlots,
        array $grades,
        int $userId
    ): void {
        $now = Carbon::now();

        // Insert keywords in bulk
        $keywordRows = [];
        for ($i = 1; $i <= $keywordsCount; $i++) {
            $keywordRows[] = [
                EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
                EvaluationKeyword::FIELD_KEYWORD              => sprintf('heavy keyword %03d %s', $i, Str::random(4)),
                EvaluationKeyword::FIELD_TOTAL_COUNT          => rand(80, 5000),
                EvaluationKeyword::FIELD_EXECUTION_CODE       => 200,
                EvaluationKeyword::FIELD_EXECUTION_MESSAGE    => 'OK',
                EvaluationKeyword::FIELD_FAILED               => false,
                EvaluationKeyword::FIELD_CREATED_AT           => $now,
                EvaluationKeyword::FIELD_UPDATED_AT           => $now,
            ];
        }

        foreach (array_chunk($keywordRows, 500) as $chunk) {
            DB::table('evaluation_keywords')->insert($chunk);
        }

        $keywordIds = DB::table('evaluation_keywords')
            ->where(EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID, $evaluation->id)
            ->orderBy(EvaluationKeyword::FIELD_ID)
            ->pluck(EvaluationKeyword::FIELD_ID)
            ->all();

        $this->info(sprintf('  Inserted %d keywords', count($keywordIds)));

        // Keyword metrics (keywords x metrics)
        $kmRows = [];
        foreach ($keywordIds as $kwId) {
            foreach ($metrics as $metric) {
                $kmRows[] = [
                    KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $kwId,
                    KeywordMetric::FIELD_EVALUATION_METRIC_ID  => $metric->id,
                    KeywordMetric::FIELD_VALUE                 => round(rand(10, 95) / 100, 4),
                    KeywordMetric::FIELD_CREATED_AT            => $now,
                    KeywordMetric::FIELD_UPDATED_AT            => $now,
                ];
            }
        }
        foreach (array_chunk($kmRows, 1000) as $chunk) {
            DB::table('keyword_metrics')->insert($chunk);
        }
        $this->info(sprintf('  Inserted %d keyword_metrics', count($kmRows)));

        // Snapshots + feedbacks
        $snapshotBuffer = [];
        $totalSnapshots = 0;
        $bar = $this->output->createProgressBar(count($keywordIds));
        $bar->start();

        foreach ($keywordIds as $kwId) {
            for ($pos = 1; $pos <= $snapshotCount; $pos++) {
                $snapshotBuffer[] = [
                    SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $kwId,
                    SearchSnapshot::FIELD_POSITION              => $pos,
                    SearchSnapshot::FIELD_DOC_ID                => 'HV-' . $kwId . '-' . $pos,
                    SearchSnapshot::FIELD_IMAGE                 => '',
                    SearchSnapshot::FIELD_NAME                  => sprintf('Heavy Doc %d/%d', $kwId, $pos),
                    SearchSnapshot::FIELD_DOC                   => json_encode([
                        'name'     => sprintf('Heavy Doc %d/%d', $kwId, $pos),
                        'price'    => rand(10, 9999),
                        'brand'    => 'HeavyBrand',
                        'category' => 'Performance',
                    ]),
                    SearchSnapshot::FIELD_CREATED_AT            => $now,
                    SearchSnapshot::FIELD_UPDATED_AT            => $now,
                ];
            }
            $totalSnapshots += $snapshotCount;

            if (count($snapshotBuffer) >= 2000) {
                $this->flushSnapshots($snapshotBuffer, $feedbackSlots, $grades, $userId, $now);
                $snapshotBuffer = [];
            }
            $bar->advance();
        }

        if (!empty($snapshotBuffer)) {
            $this->flushSnapshots($snapshotBuffer, $feedbackSlots, $grades, $userId, $now);
        }

        $bar->finish();
        $this->newLine();
        $this->info(sprintf('  Inserted %d snapshots with %d feedback slots each', $totalSnapshots, $feedbackSlots));
    }

    /**
     * @param array<int> $grades
     */
    private function flushSnapshots(array $snapshots, int $feedbackSlots, array $grades, int $userId, Carbon $now): void
    {
        foreach (array_chunk($snapshots, 500) as $chunk) {
            DB::table('search_snapshots')->insert($chunk);
        }

        // Fetch just-inserted snapshot ids by (keyword_id, position) pairs
        $pairs = array_map(fn ($s) => [
            $s[SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID],
            $s[SearchSnapshot::FIELD_POSITION],
        ], $snapshots);

        $keywordIds = array_unique(array_column($pairs, 0));

        $fetched = DB::table('search_snapshots')
            ->whereIn(SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID, $keywordIds)
            ->get([
                SearchSnapshot::FIELD_ID,
                SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID,
                SearchSnapshot::FIELD_POSITION,
            ]);

        $idMap = [];
        foreach ($fetched as $row) {
            $idMap[$row->{SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID} . ':' . $row->{SearchSnapshot::FIELD_POSITION}]
                = $row->{SearchSnapshot::FIELD_ID};
        }

        $feedbackRows = [];
        foreach ($snapshots as $s) {
            $sid = $idMap[$s[SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID] . ':' . $s[SearchSnapshot::FIELD_POSITION]] ?? null;
            if (!$sid) {
                continue;
            }

            for ($slot = 0; $slot < $feedbackSlots; $slot++) {
                // Mix: 1 slot graded by admin user, others graded anonymously (user_id=null, grade set)
                $isUserSlot = ($slot === 0);

                $feedbackRows[] = [
                    UserFeedback::FIELD_USER_ID            => $isUserSlot ? $userId : null,
                    UserFeedback::FIELD_JUDGE_ID           => null,
                    UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => $sid,
                    UserFeedback::FIELD_GRADE              => $grades[array_rand($grades)],
                    UserFeedback::FIELD_REASON             => null,
                    UserFeedback::FIELD_CREATED_AT         => $now,
                    UserFeedback::FIELD_UPDATED_AT         => $now,
                ];
            }
        }

        foreach (array_chunk($feedbackRows, 2000) as $chunk) {
            DB::table('user_feedbacks')->insert($chunk);
        }
    }
}
