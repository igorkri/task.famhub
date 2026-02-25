<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-styles', function () {
    return view('test-styles');
});

// Простий тест що Laravel працює
Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'time' => now()->toDateTimeString()]);
});

Route::get('/asana-test-projects', [\App\Http\Controllers\AsanaTestController::class, 'projects']);

// Друк акту виконаних робіт (захищено авторизацією)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/export-contractor-acts-pdf', [\App\Http\Controllers\ContractorActOfCompletedWorkPrintController::class, 'exportBulkPdf'])
        ->name('admin.contractor-act-of-completed-works.export-pdf');
    Route::get('/admin/contractor-act-of-completed-works/{act}/print', \App\Http\Controllers\ContractorActOfCompletedWorkPrintController::class)
        ->name('admin.contractor-act-of-completed-works.print');
    Route::get('/admin/contractor-act-of-completed-works/{act}/pdf', [\App\Http\Controllers\ContractorActOfCompletedWorkPrintController::class, 'pdf'])
        ->name('admin.contractor-act-of-completed-works.pdf');
});

// GET route для верифікації Viber webhook
Route::get('/viber/webhook', function (Request $request) {
    $logFile = storage_path('logs/viber_webhook.log');
    @mkdir(dirname($logFile), 0777, true);
    @file_put_contents($logFile, "\n=== GET REQUEST at ".date('Y-m-d H:i:s')." ===\n".json_encode($request->all(), JSON_PRETTY_PRINT)."\n", FILE_APPEND);

    return response()->json(['status' => 0, 'message' => 'Webhook endpoint is active']);
});

Route::post('/viber/webhook', function (Request $request) {
    // Використовуємо storage_path() щоб працювало на будь-якому сервері
    $logFile = storage_path('logs/viber_webhook.log');

    // Створюємо директорію якщо не існує
    $dir = dirname($logFile);
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // Получаем все данные запроса
    $data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $request->method(),
        'ip' => $request->ip(),
        'headers' => $request->headers->all(),
        'input' => $request->all(),
        'raw_content' => $request->getContent(),
    ];

    // Логируем в файл напрямую
    @file_put_contents(
        $logFile,
        "\n=== NEW REQUEST at ".date('Y-m-d H:i:s')." ===\n".
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
        FILE_APPEND
    );

    // Также логируем через Laravel Log
    try {
        Log::info('Viber Webhook', $data);
    } catch (\Exception $e) {
        @file_put_contents($logFile, 'LOG ERROR: '.$e->getMessage()."\n", FILE_APPEND);
    }

    // Проверяем, есть ли сообщение от пользователя
    $event = $request->input('event');

    if ($event === 'message') {
        $userId = $request->input('sender.id');
        $userName = $request->input('sender.name', 'Без имени');
        $text = $request->input('message.text', '');

        @file_put_contents($logFile, "Message from {$userName} ({$userId}): {$text}\n", FILE_APPEND);

        if ($userId) {
            try {
                $response = Http::withHeaders([
                    'X-Viber-Auth-Token' => '479d6bb020e7d3c0-10c469c78149798d-5cc4db7f99be936f',
                    'Content-Type' => 'application/json',
                ])->post('https://chatapi.viber.com/pa/send_message', [
                    'receiver' => $userId,
                    'type' => 'text',
                    'text' => "Привет, {$userName}! 👋\n\nТы написал: {$text}\n\nТвой user.id:\n{$userId}",
                ]);

                // Логируем ответ от Viber API
                @file_put_contents(
                    $logFile,
                    "=== VIBER API RESPONSE ===\n".$response->body()."\n",
                    FILE_APPEND
                );
            } catch (\Exception $e) {
                @file_put_contents($logFile, 'VIBER API ERROR: '.$e->getMessage()."\n", FILE_APPEND);
            }
        }
    }

    return response()->json(['status' => 0, 'message' => 'OK']);
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
