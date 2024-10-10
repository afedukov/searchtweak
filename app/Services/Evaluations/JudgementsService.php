<?php

namespace App\Services\Evaluations;

use App\Models\SearchEvaluation;
use App\Models\UserFeedback;

class JudgementsService
{
    /**
     * @param SearchEvaluation $evaluation
     * @param callable{float|int, string, string, int} $callback Callback function that accepts grade, keyword, doc_id and position
     *
     * @return void
     */
    public function process(SearchEvaluation $evaluation, callable $callback): void
    {
        $evaluation->loadMissing('keywords.snapshots.feedbacks');

        foreach ($evaluation->keywords as $keyword) {
            foreach ($keyword->snapshots as $snapshot) {
                $grades = $snapshot->feedbacks
                    ->whereNotNull(UserFeedback::FIELD_GRADE)
                    ->pluck(UserFeedback::FIELD_GRADE)
                    ->all();

                $grade = $evaluation->getScale()->getValue($grades);

                if ($grade !== null) {
                    $callback(
                        $grade == round($grade) ? intval($grade) : floatval(number_format($grade, 2)),
                        $keyword->keyword,
                        $snapshot->doc_id,
                        $snapshot->position
                    );
                }
            }
        }
    }
}
