#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\AirAlertService;

$airAlert = new AirAlertService();

echo "=== Тестування Air Alert API ===\n\n";

// Тест 1: Отримати список регіонів
echo "1. Список регіонів:\n";
$regions = $airAlert->getRegions();
echo "Всього регіонів: ".count($regions)."\n";
echo "Приклади:\n";
foreach (array_slice($regions, 0, 5, true) as $uid => $name) {
    echo "  {$uid}: {$name}\n";
}
echo "\n";

// Тест 2: Перевірити конкретний регіон (Харків)
echo "2. Статус Харківської області (UID=22):\n";
$kharkiv = $airAlert->getAlertByRegion('22');
if ($kharkiv) {
    echo "  Регіон: {$kharkiv['region_name']}\n";
    echo "  Тривога: ".($kharkiv['alert'] ? 'ТАК' : 'НІ')."\n";
    echo "  Статус: {$kharkiv['status_code']}\n";
} else {
    echo "  Помилка отримання даних\n";
}
echo "\n";

// Тест 3: Перевірити Київ
echo "3. Статус м. Київ (UID=31):\n";
$kyiv = $airAlert->getAlertByRegion('31');
if ($kyiv) {
    echo "  Регіон: {$kyiv['region_name']}\n";
    echo "  Тривога: ".($kyiv['alert'] ? 'ТАК' : 'НІ')."\n";
    echo "  Статус: {$kyiv['status_code']}\n";
} else {
    echo "  Помилка отримання даних\n";
}
echo "\n";

// Тест 4: Активні тривоги
echo "4. Активні тривоги по всій Україні:\n";
$active = $airAlert->getActiveAlerts();
if ($active && isset($active['alerts'])) {
    $count = count($active['alerts']);
    echo "  Всього активних тривог: {$count}\n";
    if ($count > 0) {
        echo "  Регіони:\n";
        foreach (array_slice($active['alerts'], 0, 5) as $alert) {
            echo "    - {$alert['location_title']}\n";
        }
    }
} else {
    echo "  Помилка отримання даних\n";
}
echo "\n";

echo "=== Тестування завершено ===\n";

