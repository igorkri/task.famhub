#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\AirAlertService;

echo "=== Тест API для Полтавської області ===\n\n";

$airAlert = new AirAlertService();

// Тест 1: Область через IoT
echo "1. Полтавська область (IoT endpoint, UID=19):\n";
$oblast = $airAlert->getAlertByRegion('19');
if ($oblast) {
    echo "   ✓ Назва: {$oblast['region_name']}\n";
    echo "   ✓ Тривога: " . ($oblast['alert'] ? 'ТАК' : 'НІ') . "\n";
    echo "   ✓ Статус: {$oblast['status_code']}\n";
} else {
    echo "   ✗ Помилка отримання даних\n";
}
echo "\n";

// Тест 2: Детальна інформація про громади
echo "2. Активні тривоги в Полтавській області (детально):\n";
$alerts = $airAlert->getActiveAlertsForOblast('Полтавська область');
if ($alerts !== null) {
    if (empty($alerts)) {
        echo "   ✓ Тривог немає\n";
    } else {
        echo "   ✓ Знайдено тривог: " . count($alerts) . "\n";
        foreach (array_slice($alerts, 0, 3) as $alert) {
            echo "     - {$alert['location_title']} ({$alert['alert_type']})\n";
        }
    }
} else {
    echo "   ✗ Помилка отримання даних\n";
}
echo "\n";

echo "=== Тест завершено ===\n";

