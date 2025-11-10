<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Резервна синхронізація задач з Asana (на випадок, якщо webhook не спрацював)
Schedule::command('asana:sync-tasks --hours=6 --limit=100')
    ->everySixHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Проверка графика отключений электроэнергии каждые 3 минуты
Schedule::command('power:fetch-schedule')
    ->everyThreeMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Проверка графика на следующий день с случайным интервалом от 30 до 60 минут
Schedule::call(function () {
    \Illuminate\Support\Facades\Artisan::call('power:fetch-schedule', [
        'date' => now()->addDay()->format('d-m-Y'),
    ]);
})
    ->cron('*/30 * * * *') // Каждые 30 минут
    ->skip(function () {
        // Пропускаем выполнение с вероятностью 50% для рандомизации (среднее ~45-60 мин)
        return rand(0, 1) === 0;
    })
    ->name('power:fetch-schedule-next-day')
    ->withoutOverlapping()
    ->onOneServer();

