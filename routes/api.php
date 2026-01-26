<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\GamesController;
use App\Http\Controllers\Api\GameStatusesController;
use App\Http\Controllers\Api\OperatingHourController;
use App\Http\Controllers\Api\SportsCenterController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('/sports-centers', SportsCenterController::class);

    Route::get('/sports-centers/{sports_center}/fields', [FieldController::class, 'index']);
    Route::post('/sports-centers/{sports_center}/fields', [FieldController::class, 'store']);

    Route::get(
        '/sports-centers/{sportsCenter}/operating-hours',
        [OperatingHourController::class, 'index']
    );
    Route::put(
        '/sports-centers/{sportsCenter}/operating-hours',
        [OperatingHourController::class, 'upsertBatch']
    );

    Route::put('/fields/{field}', [FieldController::class, 'update']);
    Route::delete('/fields/{field}', [FieldController::class, 'destroy']);

    Route::get('/game-statuses', [GameStatusesController::class, 'index']);

    Route::get('/games', [GamesController::class, 'index']);
    Route::get('/sports-centers/{sportsCenter}/games', [GamesController::class, 'bySportsCenter']);
    Route::patch('/games/{game}/status', [GamesController::class, 'updateStatus']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
});
