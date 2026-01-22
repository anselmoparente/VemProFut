<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SportsCenterController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'role:field_owner'])->group(function () {
    Route::apiResource('/sports-centers', SportsCenterController::class);
});

Route::middleware(['auth:sanctum', 'role:player'])->group(function () {});
