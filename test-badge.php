#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;

echo "=== Тест Badge в навигации задач ===\n\n";

// Проверяем количество активных задач
$activeCount = Task::whereIn('status', [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS])
    ->where('is_completed', false)
    ->count();

echo "Активных задач (Новий + В процесі): {$activeCount}\n";

// Проверяем badge
$badge = TaskResource::getNavigationBadge();
echo 'Badge значение: '.($badge ?? 'null')."\n";

// Проверяем цвет badge
$color = TaskResource::getNavigationBadgeColor();
echo 'Badge цвет: '.($color ?? 'null')."\n\n";

echo "Цветовая индикация:\n";
echo "- 0 задач: null (не показывается)\n";
echo "- 1-4 задачи: success (зелёный)\n";
echo "- 5-9 задач: warning (жёлтый)\n";
echo "- 10+ задач: danger (красный)\n";
