<?php

use App\Http\Controllers\Api\TournamentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\TournamentMatchController;

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
Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/tournaments/{tournament}/complete', [TournamentController::class, 'complete'])->middleware('auth:sanctum');
Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);

//Tournament Matches
Route::get('tournaments/{tournament}/matches' , [TournamentMatchController::class , 'index']);
Route::post('tournaments-matches' , [TournamentMatchController::class , 'store'])->middleware('auth:sanctum');
Route::put('tournaments-matches/{tournamentMatch}' , [TournamentMatchController::class , 'submitByAdmin'])->middleware('auth:sanctum');
Route::post('tournament-matches/{tournamentMatch}/submit-result',
    [TournamentMatchController::class, 'submitResult'])->middleware('auth:sanctum');

Route::post('test' ,);
