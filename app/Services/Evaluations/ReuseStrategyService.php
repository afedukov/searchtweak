<?php

namespace App\Services\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
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
            ->with('keywords.snapshots.feedbacks.user.tags')
            ->whereKeyNot($evaluation->id)
            ->where(SearchEvaluation::FIELD_SCALE_TYPE, $evaluation->scale_type)
            ->chunkById(5, function (Collection $evaluations) use ($strategy, $keywords, &$pool, $evaluationTags) {
                /** @var SearchEvaluation $evaluation */
                foreach ($evaluations as $evaluation) {
                    foreach ($evaluation->keywords->whereIn(EvaluationKeyword::FIELD_KEYWORD, $keywords) as $keyword) {
                        foreach ($keyword->snapshots as $snapshot) {
                            $gradedFeedbacks = $snapshot->feedbacks
                                ->whereNotNull(UserFeedback::FIELD_USER_ID)
                                ->whereNotNull(UserFeedback::FIELD_GRADE)
                                ->filter(fn (UserFeedback $feedback) =>
                                    empty($evaluationTags) || $feedback->user->tags->whereIn(UserTag::FIELD_ID, $evaluationTags)->isNotEmpty()
                                )
                                ->map(fn (UserFeedback $feedback) => [
                                    UserFeedback::FIELD_USER_ID => $feedback->user_id,
                                    UserFeedback::FIELD_GRADE => $feedback->grade,
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
            foreach ($keyword->snapshots as $snapshot) {
                $userIds = $snapshot->feedbacks
                    ->whereNotNull(UserFeedback::FIELD_GRADE)
                    ->whereNotNull(UserFeedback::FIELD_USER_ID)
                    ->pluck(UserFeedback::FIELD_USER_ID)
                    ->all();

                $feedbackPool = [];

                if ($strategy === SearchEvaluation::REUSE_STRATEGY_QUERY_DOC) {
                    $feedbackPool = $pool[$keyword->keyword][$snapshot->doc_id] ?? [];
                }

                if ($strategy === SearchEvaluation::REUSE_STRATEGY_QUERY_DOC_POSITION) {
                    $feedbackPool = $pool[$keyword->keyword][$snapshot->doc_id][$snapshot->position] ?? [];
                }

                foreach ($snapshot->feedbacks as $feedback) {
                    if ($feedback->grade !== null || $feedback->user_id !== null) {
                        continue;
                    }

                    $feedbackPool = array_filter($feedbackPool, fn (array $f) => !in_array($f[UserFeedback::FIELD_USER_ID], $userIds));

                    $reuseFeedback = array_pop($feedbackPool);
                    if ($reuseFeedback === null) {
                        break;
                    }

                    $feedback->user_id = $reuseFeedback[UserFeedback::FIELD_USER_ID];
                    $feedback->grade = $reuseFeedback[UserFeedback::FIELD_GRADE];
                    $feedback->save();

                    $userIds[] = $feedback->user_id;
                }
            }
        }
    }
}
