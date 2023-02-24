<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Note: all routes use the ApiKeyMiddleware - set in app/Http/Kernel.php
Route::get('/students', [App\Http\Controllers\Api\StudentController::class, 'index']);
Route::get('/overduemeetings', [App\Http\Controllers\Api\OverdueMeetingController::class, 'index']);
Route::get('/supervisors', [App\Http\Controllers\Api\SupervisorController::class, 'index']);
Route::get('/supervisor/{user:username}', [App\Http\Controllers\Api\SupervisorController::class, 'show']);
