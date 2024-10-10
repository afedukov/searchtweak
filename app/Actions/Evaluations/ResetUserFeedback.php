<?php

namespace App\Actions\Evaluations;

use App\Models\SearchEvaluation;
use App\Models\UserFeedback;
use Illuminate\Support\Facades\Gate;

class ResetUserFeedback
{
    public function reset(SearchEvaluation $evaluation, UserFeedback $feedback): void
    {
        Gate::authorize('resetFeedback', $evaluation);

        if ($evaluation->isFinished()) {
            throw new \RuntimeException('Failed to reset user feedback: evaluation is finished.');
        }

        $feedback->update([
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ]);
    }
}
