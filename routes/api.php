<?php

use App\Http\Controllers\Api\TournamentController;
use App\Http\Controllers\Api\TournamentMatchController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Public Tournament Access
Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);
Route::get('/tournaments/{tournament}/players', [TournamentController::class, 'players']);
Route::get('tournaments/{tournament}/matches', [TournamentMatchController::class, 'index']);
Route::get('plans', [PlanController::class, 'index']);
Route::get('plans/{plan}', [PlanController::class, 'show']);

// The game catalogue is public to read; changing it needs an admin.
Route::get('games', [GameController::class, 'index']);
Route::get('games/{game}', [GameController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Authenticated via Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth Management
    Route::post('logout', [AuthController::class, 'logout']);

    // Platform Management
    Route::apiResource('platform', PlatformController::class)->except('show');

    // Tournament Management
    Route::prefix('tournaments/{tournament}')->group(function () {
        Route::post('sign-up', [TournamentController::class, 'signUp']);
        // No sign-out route: TournamentController::signOut() is commented out,
        // so routing to it answered 500 for every authenticated caller. Put
        // this back with the controller method, not before it.
    });

    // Tournament CRUD & Matches
    Route::apiResource('tournaments', TournamentController::class)->except(['index', 'show']);
    Route::post('tournaments/{tournament}/matches', [TournamentMatchController::class, 'store']);

    // Match Results & Submissions
    Route::prefix('tournament-matches')->group(function () {
        Route::put('{tournamentMatch}/submit-by-admin', [TournamentMatchController::class, 'submitByAdmin']); // Cleaned up URL
        Route::post('{tournamentMatch}/submit-result', [TournamentMatchController::class, 'submitByPlayer']);
    });

    // Subscription & Plans
    Route::get('subscription', [SubscriptionController::class, 'show']);
    Route::post('subscription/plans/{plan}', [SubscriptionController::class, 'store']);

    Route::apiResource('plans', PlanController::class)->except(['show', 'index']);
    Route::apiResource('games', GameController::class)->except(['show', 'index']);
});
