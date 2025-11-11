#!/usr/bin/env php
<?php

$token = '8a0343dfa946b66b0b4c7b6e6c1f867076ea1a74ab2203';

echo "=== Тестування Air Alert API ===\n\n";

// Тест 1: Активні тривоги
echo "1. Тест активних тривог:\n";
$url = "https://api.alerts.in.ua/v1/alerts/active.json?token={$token}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "Активних тривог: ".count($data['alerts'] ?? [])."\n";
    if (!empty($data['alerts'])) {
        echo "Перші 3 тривоги:\n";
        foreach (array_slice($data['alerts'], 0, 3) as $alert) {
            echo "  - {$alert['location_title']} (UID: {$alert['location_uid']})\n";
        }
    }
} else {
    echo "Помилка: {$response}\n";
}
echo "\n";

// Тест 2: IoT endpoint для конкретного регіону
echo "2. Тест IoT endpoint (Київ, UID=31):\n";
$url = "https://api.alerts.in.ua/v1/iot/active_air_raid_alerts/31.json?token={$token}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response: {$response}\n";
echo "\n";

// Тест 3: Статуси по областях
echo "3. Тест статусів по областях:\n";
$url = "https://api.alerts.in.ua/v1/iot/active_air_raid_alerts_by_oblast.json?token={$token}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
$status = trim($response, '"');
echo "Status string length: ".strlen($status)."\n";
echo "First 10 chars: ".substr($status, 0, 10)."\n";
echo "\n";

echo "=== Тестування завершено ===\n";

