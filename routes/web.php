<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-styles', function () {
    return view('test-styles');
});

Route::get('/asana-test-projects', [\App\Http\Controllers\AsanaTestController::class, 'projects']);

//Route::get('/viber/webhook', function (Request $request) {
//    file_put_contents(storage_path('logs/test_viber.txt'), "Webhook вызван\n", FILE_APPEND);
//    return response()->json(['status' => 0]);
//});

Route::post('/viber/webhook', function (Request $request) {
    // Логируем всё в файл
    \Log::info('Viber Webhook', $request->all());

    // Проверяем, есть ли сообщение от пользователя
    if (isset($request['event']) && $request['event'] === 'message') {
        $userId = $request['sender']['id'] ?? null;
        $userName = $request['sender']['name'] ?? 'Без имени';
        $text = $request['message']['text'] ?? '';

        if ($userId) {
            Http::withHeaders([
                'X-Viber-Auth-Token' => '479d6bb020e7d3c0-10c469c78149798d-5cc4db7f99be936f',
                'Content-Type' => 'application/json',
            ])->post('https://chatapi.viber.com/pa/send_message', [
                'receiver' => $userId,
                'type' => 'text',
                'text' => "Привет, {$userName}! 👋\n\nТы написал: {$text}\n\nТвой user.id:\n{$userId}",
            ]);
        }
    }

    return response()->json(['status' => 0]);
});
/*
    curl -X POST \
  -H "X-Viber-Auth-Token: 479d6bb020e7d3c0-10c469c78149798d-5cc4db7f99be936f" \
  -H "Content-Type: application/json" \
  -d '{
        "url": "https://task.dev2025.ingsot.com/viber/webhook",
        "event_types": ["message", "conversation_started"]
      }' \
    https://chatapi.viber.com/pa/set_webhook
*/

