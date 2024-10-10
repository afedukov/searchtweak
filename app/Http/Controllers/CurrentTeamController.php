<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Jetstream\Jetstream;

class CurrentTeamController extends Controller
{
    private const array ROUTE_EXCEPTIONS = [
        'evaluation' => 'evaluations',
        'evaluation.feedback' => 'evaluations',
        'model' => 'models',
    ];

    public function update(Request $request): RedirectResponse
    {
        $team = Jetstream::newTeamModel()->findOrFail($request->get('team_id'));

        if (!$request->user()->switchTeam($team)) {
            abort(403);
        }

        $redirectTo = $request->get('redirect');

        if ($redirectTo) {
            if (array_key_exists($redirectTo, self::ROUTE_EXCEPTIONS)) {
                $redirectTo = self::ROUTE_EXCEPTIONS[$redirectTo];
            }
            $redirect = route($redirectTo);
        } else {
            $redirect = config('fortify.home');
        }

        return redirect($redirect, 303);
    }
}
