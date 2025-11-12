<?php

$logFile = '/home/igor/developer/task.famhub.local/storage/logs/viber_direct_test.log';

// Створюємо директорію
@mkdir(dirname($logFile), 0777, true);

// Отримуємо всі дані
$data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'raw_post' => file_get_contents('php://input'),
    'post' => $_POST,
    'get' => $_GET,
    'headers' => getallheaders(),
    'server' => $_SERVER,
];

// Записуємо в файл
file_put_contents(
    $logFile,
    "\n=== REQUEST at " . date('Y-m-d H:i:s') . " ===\n" .
    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
    FILE_APPEND
);

// Відповідь
header('Content-Type: application/json');
echo json_encode(['status' => 0, 'message' => 'Logged successfully', 'file' => $logFile]);

