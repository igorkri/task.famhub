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
