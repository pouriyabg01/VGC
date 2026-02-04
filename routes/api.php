<?php

use App\Http\Controllers\Api\TournamentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\TournamentMatchController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;

Route::post('register' , [AuthController::class , 'register']);
Route::post('login' , [AuthController::class , 'login']);
Route::middleware('auth:sanctum')->group(function (){
   Route::post('logout' , [AuthController::class , 'logout']);
});

//user's platforms
Route::apiResource('platform' , PlatformController::class)
    ->middleware('auth:sanctum')
    ->except('show');

//Tournament
Route::post('/tournaments', [TournamentController::class, 'store'])->middleware('auth:sanctum');
Route::put('/tournaments/{tournament}', [TournamentController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);
Route::get('/tournaments/{tournament}/players', [TournamentController::class, 'players']);
Route::post('/tournaments/{tournament}/sign-up' , [TournamentController::class , 'signUp'])->middleware('auth:sanctum');

//Tournament Matches
Route::get('tournaments/{tournament}/matches' , [TournamentMatchController::class , 'index']);
Route::post('tournaments/{tournament}/matches' , [TournamentMatchController::class , 'store'])->middleware('auth:sanctum');
Route::put('tournaments-matches/{tournamentMatch}' , [TournamentMatchController::class , 'submitByAdmin'])->middleware('auth:sanctum');
Route::post('tournament-matches/{tournamentMatch}/submit-result',
    [TournamentMatchController::class, 'submitByPlayer'])->middleware('auth:sanctum');

//subscription
Route::post('subscription/plans/{plan}' ,[SubscriptionController::class , 'store'])->middleware('auth:sanctum');
Route::get('subscription' ,[SubscriptionController::class , 'show'])->middleware('auth:sanctum');

//plans
Route::apiResource('plans' , PlanController::class)
    ->except('show')->middleware('auth:sanctum');
Route::get('plans/{plan}' , [PlanController::class , 'show']);
Route::post('test' , [PlanController::class , 'test'])->middleware('auth:sanctum');
