<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-styles', function () {
    return view('test-styles');
});

Route::get('/asana-test-projects', [\App\Http\Controllers\AsanaTestController::class, 'projects']);

Route::post('/viber/webhook', function (Request $request) {
    file_put_contents(storage_path('logs/test_viber.txt'), "Webhook вызван\n", FILE_APPEND);
    return response()->json(['status' => 0]);
});

//Route::post('/viber/webhook', function (Request $request) {
//    $data = $request->all();
//
//    // Логируем всё в storage/logs/viber_log.txt
//    Storage::disk('logs')->append('viber_log.txt', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
//
//    // Если это обычное сообщение от пользователя
//    if (isset($data['event']) && $data['event'] === 'message') {
//        $userId = $data['sender']['id'] ?? null;
//        $userName = $data['sender']['name'] ?? 'Без имени';
//        $text = $data['message']['text'] ?? '';
//
//        if ($userId) {
//            Http::withHeaders([
//                'X-Viber-Auth-Token' => '479d6bb020e7d3c0-10c469c78149798d-5cc4db7f99be936f',
//                'Content-Type' => 'application/json',
//            ])->post('https://chatapi.viber.com/pa/send_message', [
//                'receiver' => $userId,
//                'type' => 'text',
//                'text' => "Привет, {$userName}! 👋\n\nТы написал: {$text}\n\nТвой user.id:\n{$userId}",
//            ]);
//        }
//    }
//
//    return response()->json(['status' => 0]);
//});
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

