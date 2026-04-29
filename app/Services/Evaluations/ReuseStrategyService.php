<?php

namespace App\Services\Evaluations;

use App\Jobs\Evaluations\RecalculateMetricsJob;
use App\Models\EvaluationKeyword;
use App\Models\Judge;
use App\Models\SearchEvaluation;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserFeedback;
use App\Models\UserTag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReuseStrategyService
{
    /**
     * Number of current-eval keywords processed per pool-build round. Bounds peak
     * memory: each round only loads its own keywords' snapshots/feedbacks and only
     * pulls the team-history pool slice for that keyword subset.
     */
    private const int KEYWORD_CHUNK_SIZE = 10;

    public function apply(SearchEvaluation $evaluation): void
    {
        $strategy = $evaluation->getReuseStrategy();
        if (!in_array($strategy, [SearchEvaluation::REUSE_STRATEGY_QUERY_DOC, SearchEvaluation::REUSE_STRATEGY_QUERY_DOC_POSITION])) {
            throw new \InvalidArgumentException('Invalid reuse strategy');
        }

        $evaluation->load('model', 'tags');

        $teamId = $evaluation->model->team_id;
        $evaluationTags = $evaluation->tags->modelKeys();

        $anyUpdated = false;

        $evaluation->keywords()
            ->orderBy(EvaluationKeyword::FIELD_ID)
            ->chunkById(self::KEYWORD_CHUNK_SIZE, function (Collection $keywordChunk) use ($evaluation, $teamId, $strategy, $evaluationTags, &$anyUpdated) {
                $keywordChunk->load('snapshots.feedbacks');

                $chunkKeywordTexts = $keywordChunk->pluck(EvaluationKeyword::FIELD_KEYWORD)->all();
                $pool = $this->buildPool($evaluation, $teamId, $chunkKeywordTexts, $strategy, $evaluationTags);

                foreach ($keywordChunk as $keyword) {
                    if ($this->applyKeywordReuse($keyword, $pool, $strategy)) {
                        $anyUpdated = true;
                    }
                }
            });

        if ($anyUpdated) {
            UserFeedbackService::flushUngradedSnapshotsCountCache($teamId);
        }
    }

    private function applyKeywordReuse(EvaluationKeyword $keyword, array $pool, int $strategy): bool
    {
        $updated = false;

        foreach ($keyword->snapshots as $snapshot) {
            $userIds = $snapshot->feedbacks
                ->whereNotNull(UserFeedback::FIELD_GRADE)
                ->whereNotNull(UserFeedback::FIELD_USER_ID)
                ->pluck(UserFeedback::FIELD_USER_ID)
                ->all();
            $judgeIds = $snapshot->feedbacks
                ->whereNotNull(UserFeedback::FIELD_GRADE)
                ->whereNotNull(UserFeedback::FIELD_JUDGE_ID)
                ->pluck(UserFeedback::FIELD_JUDGE_ID)
                ->all();

            $feedbackPool = [];

            if ($strategy === SearchEvaluation::REUSE_STRATEGY_QUERY_DOC) {
                $feedbackPool = $pool[$keyword->keyword][$snapshot->doc_id] ?? [];
            }

            if ($strategy === SearchEvaluation::REUSE_STRATEGY_QUERY_DOC_POSITION) {
                $feedbackPool = $pool[$keyword->keyword][$snapshot->doc_id][$snapshot->position] ?? [];
            }

            foreach ($snapshot->feedbacks as $feedback) {
                if ($feedback->grade !== null || $feedback->user_id !== null || $feedback->judge_id !== null) {
                    continue;
                }

                $feedbackPool = array_values(array_filter($feedbackPool, function (array $f) use ($userIds, $judgeIds) {
                    $userId = $f[UserFeedback::FIELD_USER_ID] ?? null;
                    $judgeId = $f[UserFeedback::FIELD_JUDGE_ID] ?? null;

                    if ($userId !== null) {
                        return !in_array($userId, $userIds, true);
                    }

                    if ($judgeId !== null) {
                        return !in_array($judgeId, $judgeIds, true);
                    }

                    return false;
                }));

                $reuseFeedback = array_pop($feedbackPool);
                if ($reuseFeedback === null) {
                    break;
                }

                $feedback->user_id = $reuseFeedback[UserFeedback::FIELD_USER_ID] ?? null;
                $feedback->judge_id = $reuseFeedback[UserFeedback::FIELD_JUDGE_ID] ?? null;
                $feedback->reason = $reuseFeedback[UserFeedback::FIELD_REASON] ?? null;
                $feedback->grade = $reuseFeedback[UserFeedback::FIELD_GRADE];
                $feedback->saveQuietly();

                $updated = true;

                $userIds[] = $feedback->user_id;
                $judgeIds[] = $feedback->judge_id;
            }
        }

        if ($updated) {
            RecalculateMetricsJob::dispatch($keyword->id);
        }

        return $updated;
    }

    /**
     * Build the reuse pool with a single SQL query against finished evaluations
     * of the team, then bulk-resolve tag rules in PHP.
     *
     * Returns a nested array keyed by keyword text and doc_id (and position
     * for the query-doc-position strategy), each leaf being a list of
     * candidate feedback rows.
     */
    private function buildPool(
        SearchEvaluation $evaluation,
        int $teamId,
        array $keywords,
        int $strategy,
        array $evaluationTags,
    ): array {
        $pool = [];
        if (empty($keywords)) {
            return $pool;
        }

        $rows = DB::table('user_feedbacks')
            ->select([
                'user_feedbacks.user_id',
                'user_feedbacks.judge_id',
                'user_feedbacks.grade',
                'user_feedbacks.reason',
                'evaluation_keywords.keyword',
                'search_snapshots.doc_id',
                'search_snapshots.position',
            ])
            ->join('search_snapshots', 'search_snapshots.id', '=', 'user_feedbacks.search_snapshot_id')
            ->join('evaluation_keywords', 'evaluation_keywords.id', '=', 'search_snapshots.evaluation_keyword_id')
            ->join('search_evaluations', 'search_evaluations.id', '=', 'evaluation_keywords.search_evaluation_id')
            ->join('search_models', 'search_models.id', '=', 'search_evaluations.model_id')
            ->where('search_models.team_id', $teamId)
            ->where('search_evaluations.status', SearchEvaluation::STATUS_FINISHED)
            ->where('search_evaluations.archived', false)
            ->where('search_evaluations.scale_type', $evaluation->scale_type)
            ->where('search_evaluations.id', '!=', $evaluation->id)
            ->whereIn('evaluation_keywords.keyword', $keywords)
            ->whereNotNull('user_feedbacks.grade')
            ->where(function ($q) {
                $q->whereNotNull('user_feedbacks.user_id')
                    ->orWhereNotNull('user_feedbacks.judge_id');
            })
            ->orderBy('search_evaluations.id')
            ->orderBy('evaluation_keywords.id')
            ->orderBy('search_snapshots.id')
            ->orderBy('user_feedbacks.id')
            ->get();

        if ($rows->isEmpty()) {
            return $pool;
        }

        // MySQL `IN` matches per the column collation (typically case-insensitive).
        $exactKeywords = array_flip($keywords);
        $rows = $rows->filter(fn (object $row) => isset($exactKeywords[$row->keyword]));

        if ($rows->isEmpty()) {
            return $pool;
        }

        [$users, $judges] = $this->loadGraderTags($rows);

        foreach ($rows as $row) {
            if (!$this->isCandidateReusable($row, $users, $judges, $evaluationTags)) {
                continue;
            }

            $entry = [
                UserFeedback::FIELD_USER_ID => $row->user_id !== null ? (int) $row->user_id : null,
                UserFeedback::FIELD_JUDGE_ID => $row->judge_id !== null ? (int) $row->judge_id : null,
                UserFeedback::FIELD_GRADE => (int) $row->grade,
                UserFeedback::FIELD_REASON => $row->reason,
            ];

            if ($strategy === SearchEvaluation::REUSE_STRATEGY_QUERY_DOC) {
                $pool[$row->keyword][$row->doc_id][] = $entry;
            }

            if ($strategy === SearchEvaluation::REUSE_STRATEGY_QUERY_DOC_POSITION) {
                $pool[$row->keyword][$row->doc_id][(int) $row->position][] = $entry;
            }
        }

        return $pool;
    }

    /**
     * @return array{0: Collection<int, User>, 1: Collection<int, Judge>}
     */
    private function loadGraderTags(Collection $rows): array
    {
        $userIds = $rows->pluck('user_id')->filter()->unique()->values()->all();
        $judgeIds = $rows->pluck('judge_id')->filter()->unique()->values()->all();

        $users = empty($userIds)
            ? collect()
            : User::with('tags')->whereIn('id', $userIds)->get()->keyBy('id');

        $judges = empty($judgeIds)
            ? collect()
            : Judge::with('tags')->whereIn('id', $judgeIds)->get()->keyBy('id');

        return [$users, $judges];
    }

    private function isCandidateReusable(
        object $row,
        Collection $users,
        Collection $judges,
        array $evaluationTags,
    ): bool {
        if (empty($evaluationTags)) {
            return $row->user_id !== null || $row->judge_id !== null;
        }

        if ($row->user_id !== null) {
            $user = $users->get((int) $row->user_id);

            return $user !== null
                && $user->tags->whereIn(UserTag::FIELD_ID, $evaluationTags)->count() === count($evaluationTags);
        }

        if ($row->judge_id !== null) {
            $judge = $judges->get((int) $row->judge_id);
            if ($judge === null || $judge->tags->isEmpty()) {
                return false;
            }

            return collect($evaluationTags)
                ->diff($judge->tags->pluck(Tag::FIELD_ID))
                ->isEmpty();
        }

        return false;
    }
}
