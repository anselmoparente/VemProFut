<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::redirect("/", "/home");
Route::resource('/home', HomeController::class)->only(['index']);
