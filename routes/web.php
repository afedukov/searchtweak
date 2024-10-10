<?php

use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Middleware\UserOnline;
use App\Livewire\Dashboard;
use App\Livewire\Endpoints;
use App\Livewire\Evaluation;
use App\Livewire\Evaluations;
use App\Livewire\GiveFeedback;
use App\Livewire\Feedbacks;
use App\Livewire\Leaderboard;
use App\Livewire\Model;
use App\Livewire\Models;
use App\Livewire\Superuser\Users;
use App\Livewire\Teams;
use App\Livewire\CurrentTeam;
use App\Livewire\UserProfile;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CurrentTeamController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware(['auth', config('jetstream.auth_session'), 'verified', UserOnline::class])->group(function () {

    Route::name('home')->get('/', fn () => redirect()->route('dashboard'));
    Route::name('contact')->get('/contact', fn () => redirect('https://searchtweak.com/contact'));

    Route::name('profile.show')->get('/user/profile', UserProfile::class);

    Route::name('teams')
        ->group(function () {
            Route::name('.all')->get('/teams', Teams::class);
            Route::name('.current')->get('/teams/current', CurrentTeam::class);
        });

    Route::name('endpoints')
        ->get('/endpoints', Endpoints::class)
        ->middleware('can:view-endpoints');

    Route::name('models')
        ->get('/models', Models::class)
        ->middleware('can:view-models');

    Route::name('model')
        ->get('/models/{model}', Model::class)
        ->where('model', '[0-9]+')
        ->middleware('can:view,model');

    Route::name('evaluations')
        ->get('/evaluations', Evaluations::class)
        ->middleware('can:view-evaluations');

    Route::name('evaluation')
        ->get('/evaluations/{evaluation}', Evaluation::class)
        ->where('evaluation', '[0-9]+')
        ->middleware(['can:view,evaluation']);

    Route::name('evaluation.feedback')
        ->get('/evaluations/{evaluation}/feedback', Feedbacks::class)
        ->where('evaluation', '[0-9]+')
        ->middleware(['can:viewFeedback,evaluation']);

    Route::name('feedback')
        ->get('/feedback/{evaluationId?}', GiveFeedback::class)
        ->where('evaluation', '[0-9]+');

    Route::name('leaderboard')
        ->get('/leaderboard', Leaderboard::class)
        ->middleware('can:view-leaderboard');

    Route::name('dashboard')->get('/dashboard', Dashboard::class);

    Route::name('current-team.update')
        ->put('/current-team', [CurrentTeamController::class, 'update']);

    Route::name('team-invitations.accept')
        ->get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
        ->middleware(['signed']);

    // Superuser routes
    Route::name('superuser.')
        ->prefix('admin')
        ->middleware('can:superuser')
        ->group(function () {
            Route::name('users')
                ->get('/users', Users::class);

            Route::name('impersonate')
                ->get('/impersonate/{user}', [ImpersonateController::class, 'impersonate'])
                ->where('user', '[0-9]+');
        });

    Route::fallback(fn () => view('pages/utility/404'));
});
