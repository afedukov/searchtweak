<?php

namespace App\Services\Evaluations;

use App\Jobs\Evaluations\RecalculateMetricsJob;
use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use App\Models\Tag;
use App\Models\UserFeedback;
use App\Models\UserTag;
use Illuminate\Database\Eloquent\Collection;

class ReuseStrategyService
{
    public function apply(SearchEvaluation $evaluation): void
    {
        $strategy = $evaluation->getReuseStrategy();
        if (!in_array($strategy, [SearchEvaluation::REUSE_STRATEGY_QUERY_DOC, SearchEvaluation::REUSE_STRATEGY_QUERY_DOC_POSITION])) {
            throw new \InvalidArgumentException('Invalid reuse strategy');
        }

        $evaluation->load('model', 'tags', 'keywords.snapshots.feedbacks');

        $teamId = $evaluation->model->team_id;

        $keywords = $evaluation->keywords
            ->pluck(EvaluationKeyword::FIELD_KEYWORD)
            ->all();

        $evaluationTags = $evaluation->tags->modelKeys();

        $pool = [];

        SearchEvaluation::team($teamId)
            ->finished()
            ->with('keywords.snapshots.feedbacks.user.tags', 'keywords.snapshots.feedbacks.judge.tags')
            ->whereKeyNot($evaluation->id)
            ->where(SearchEvaluation::FIELD_SCALE_TYPE, $evaluation->scale_type)
            ->where(SearchEvaluation::FIELD_ARCHIVED, false)
            ->chunkById(5, function (Collection $evaluations) use ($strategy, $keywords, &$pool, $evaluationTags) {
                /** @var SearchEvaluation $evaluation */
                foreach ($evaluations as $evaluation) {
                    foreach ($evaluation->keywords->whereIn(EvaluationKeyword::FIELD_KEYWORD, $keywords) as $keyword) {
                        foreach ($keyword->snapshots as $snapshot) {
                            $gradedFeedbacks = $snapshot->feedbacks
                                ->whereNotNull(UserFeedback::FIELD_GRADE)
                                ->filter(fn (UserFeedback $feedback) => $this->isReusableFeedback($feedback, $evaluationTags))
                                ->map(fn (UserFeedback $feedback) => [
                                    UserFeedback::FIELD_USER_ID => $feedback->user_id,
                                    UserFeedback::FIELD_JUDGE_ID => $feedback->judge_id,
                                    UserFeedback::FIELD_GRADE => $feedback->grade,
                                    UserFeedback::FIELD_REASON => $feedback->reason,
                                ])
                                ->all();

                            if (empty($gradedFeedbacks)) {
                                continue;
                            }

                            // query-doc strategy
                            if ($strategy === SearchEvaluation::REUSE_STRATEGY_QUERY_DOC) {
                                $pool[$keyword->keyword][$snapshot->doc_id] = array_merge(
                                    $pool[$keyword->keyword][$snapshot->doc_id] ?? [],
                                    $gradedFeedbacks
                                );
                            }

                            // query-doc-position strategy
                            if ($strategy === SearchEvaluation::REUSE_STRATEGY_QUERY_DOC_POSITION) {
                                $pool[$keyword->keyword][$snapshot->doc_id][$snapshot->position] = array_merge(
                                    $pool[$keyword->keyword][$snapshot->doc_id][$snapshot->position] ?? [],
                                    $gradedFeedbacks
                                );
                            }
                        }
                    }
                }
            });

        foreach ($evaluation->keywords as $keyword) {
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

                // Flush ungraded snapshots count cache for the whole team
                UserFeedbackService::flushUngradedSnapshotsCountCache($teamId);
            }
        }
    }

    private function isReusableFeedback(UserFeedback $feedback, array $evaluationTags): bool
    {
        if ($feedback->grade === null) {
            return false;
        }

        if ($feedback->user_id === null && $feedback->judge_id === null) {
            return false;
        }

        if (empty($evaluationTags)) {
            return true;
        }

        if ($feedback->user_id !== null) {
            return $feedback->user !== null
                && $feedback->user->tags->whereIn(UserTag::FIELD_ID, $evaluationTags)->count() === count($evaluationTags);
        }

        if ($feedback->judge_id !== null) {
            return $feedback->judge !== null
                && $feedback->judge->tags->pluck(Tag::FIELD_ID)->diff($evaluationTags)->isEmpty();
        }

        return false;
    }
}
