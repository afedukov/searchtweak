<?php

namespace App\Actions\Evaluations;

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use App\Models\User;
use App\Notifications\EvaluationFinishNotification;
use App\Policies\Permissions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

readonly class FinishSearchEvaluation
{
    public function __construct(private AutoRestartSearchEvaluation $autoRestartSearchEvaluation)
    {
    }

    public function finish(SearchEvaluation $evaluation, bool $allowRestart = true): void
    {
        if ($evaluation->isFinished()) {
            throw new \RuntimeException('Failed to finish evaluation: evaluation is already finished');
        }

        $evaluation->status = SearchEvaluation::STATUS_FINISHED;
        $evaluation->finished_at = Carbon::now();
        $evaluation->metrics->each(fn (EvaluationMetric $metric) => $metric->touchFinishedAt());
        $evaluation->save();

        $this->notify($evaluation);

        if ($allowRestart && $evaluation->autoRestart()) {
            $this->autoRestartSearchEvaluation->restart($evaluation);
        }
    }

    private function notify(SearchEvaluation $evaluation): void
    {
        $users = $evaluation->model->team->allUsers()
            ->filter(fn (User $user) => $user->hasTeamPermission($evaluation->model->team, Permissions::PERMISSION_MANAGE_SEARCH_EVALUATIONS));

        Notification::send($users, new EvaluationFinishNotification($evaluation));
    }
}
