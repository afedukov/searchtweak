<?php

use App\Http\Controllers\Api\EvaluationsController;
use App\Http\Controllers\Api\ModelsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')
    ->prefix('v1')
    ->group(function () {

        // Evaluations
        Route::get('/evaluations', [EvaluationsController::class, 'index']);
        Route::get('/evaluations/{id}', [EvaluationsController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/evaluations/{id}/judgements', [EvaluationsController::class, 'judgements'])->where('id', '[0-9]+');
        Route::post('/evaluations/{id}/start', [EvaluationsController::class, 'start'])->where('id', '[0-9]+');
        Route::post('/evaluations/{id}/stop', [EvaluationsController::class, 'stop'])->where('id', '[0-9]+');
        Route::post('/evaluations/{id}/finish', [EvaluationsController::class, 'finish'])->where('id', '[0-9]+');
        Route::delete('/evaluations/{id}', [EvaluationsController::class, 'delete'])->where('id', '[0-9]+');
        Route::post('/evaluations', [EvaluationsController::class, 'store']);

        // Models
        Route::get('/models', [ModelsController::class, 'index']);
        Route::get('/models/{id}', [ModelsController::class, 'show'])->where('id', '[0-9]+');

    });
