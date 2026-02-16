<?php

namespace Tests\Unit\Services\Scorers\Concerns;

use App\Models\EvaluationKeyword;
use App\Models\SearchSnapshot;
use App\Models\UserFeedback;
use Illuminate\Database\Eloquent\Collection;

/**
 * Helper trait for building in-memory scorer test data without touching the database.
 */
trait CreatesScorerTestData
{
    /**
     * Create an EvaluationKeyword stub with snapshots and feedbacks from an array of grades by position.
     *
     * @param array<int, array<int|null>> $gradesByPosition  e.g. [1 => [1], 2 => [0], 3 => [1]]
     * @return EvaluationKeyword
     */
    protected function createKeywordWithFeedbacks(array $gradesByPosition): EvaluationKeyword
    {
        $snapshots = new Collection();

        foreach ($gradesByPosition as $position => $grades) {
            $feedbacks = new Collection();

            foreach ($grades as $grade) {
                $feedback = new UserFeedback();
                $feedback->grade = $grade;
                $feedback->user_id = $grade !== null ? 1 : null;
                $feedbacks->push($feedback);
            }

            $snapshot = new SearchSnapshot();
            $snapshot->position = $position;
            $snapshot->setRelation('feedbacks', $feedbacks);
            $snapshots->push($snapshot);
        }

        $keyword = new EvaluationKeyword();
        $keyword->setRelation('snapshots', $snapshots);

        return $keyword;
    }
}
