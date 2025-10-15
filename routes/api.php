<?php

use App\Http\Controllers\Api\TaskTimerController;
use App\Http\Controllers\AsanaWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/task/{task}/timer', [TaskTimerController::class, 'store']);
Route::post('/task/{task}/timer/pause', [TaskTimerController::class, 'pause']);
Route::post('/task/{task}/timer/complete', [TaskTimerController::class, 'complete']);
Route::get('/task/{task}/timer', [TaskTimerController::class, 'show']);

// Asana Webhook endpoint
Route::post('/webhooks/asana', [AsanaWebhookController::class, 'handle'])
    ->name('webhooks.asana');

Route::get('/test-api', function () {
    \Illuminate\Support\Facades\Log::info('test-api route hit');

    return ['ok' => true];
});
