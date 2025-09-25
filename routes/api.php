<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskTimerController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/task/{task}/timer', [TaskTimerController::class, 'store']);
    Route::get('/task/{task}/timer', [TaskTimerController::class, 'show']);
});
