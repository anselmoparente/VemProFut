<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\SportsCenterController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('/sports-centers', SportsCenterController::class);

    Route::get('/sports-centers/{sports_center}/fields', [FieldController::class, 'index']);
    Route::post('/sports-centers/{sports_center}/fields', [FieldController::class, 'store']);

    Route::put('/fields/{field}', [FieldController::class, 'update']);
    Route::delete('/fields/{field}', [FieldController::class, 'destroy']);
});
