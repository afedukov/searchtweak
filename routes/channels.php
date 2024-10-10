<?php

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('team.{teamId}', function (User $user, int $teamId) {
    return $user->current_team_id === $teamId;
});

Broadcast::channel('metric-value.{metricId}', function (User $user, int $metricId) {
    return EvaluationMetric::find($metricId)?->evaluation->model->team_id === $user->current_team_id;
});

Broadcast::channel('search-evaluation.{evaluationId}', function (User $user, int $evaluationId) {
    return SearchEvaluation::find($evaluationId)?->model->team_id === $user->current_team_id;
});
