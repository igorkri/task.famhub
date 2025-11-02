<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Imports\ReceiptOfFundsCsvImport;
use Maatwebsite\Excel\Facades\Excel;

// Путь к тестовому файлу
$filePath = __DIR__.'/docs/export.csv';

if (!file_exists($filePath)) {
    echo "❌ Файл не найден: {$filePath}\n";
    exit(1);
}

echo "📄 Импортируем файл: {$filePath}\n\n";

try {
    $import = new ReceiptOfFundsCsvImport;
    Excel::import($import, $filePath);

    echo "✅ Импорт завершён!\n\n";
    echo "📊 Статистика:\n";
    echo "• Импортировано: {$import->getImportedCount()}\n";
    echo "• Пропущено: {$import->getSkippedCount()}\n\n";

    if ($import->getWarnings()) {
        echo "⚠️  Предупреждения:\n";
        foreach (array_slice($import->getWarnings(), 0, 5) as $warning) {
            echo "  - {$warning}\n";
        }
        if (count($import->getWarnings()) > 5) {
            echo "  ... и ещё " . (count($import->getWarnings()) - 5) . " предупреждений\n";
        }
        echo "\n";
    }

    if ($import->getErrors()) {
        echo "❌ Ошибки:\n";
        foreach (array_slice($import->getErrors(), 0, 5) as $error) {
            echo "  - {$error}\n";
        }
        if (count($import->getErrors()) > 5) {
            echo "  ... и ещё " . (count($import->getErrors()) - 5) . " ошибок\n";
        }
        echo "\n";
    }

    // Проверяем последнюю импортированную запись
    $lastRecord = \App\Models\ActOfWork::where('type', \App\Models\ActOfWork::TYPE_RECEIPT_OF_FUNDS)
        ->latest()
        ->first();

    if ($lastRecord) {
        echo "📝 Последняя импортированная запись:\n";
        echo "  ID: {$lastRecord->id}\n";
        echo "  Номер: {$lastRecord->number}\n";
        echo "  Дата: {$lastRecord->date->format('d.m.Y')}\n";
        echo "  Сумма: {$lastRecord->total_amount}\n";
        echo "  Описание: " . mb_substr($lastRecord->description, 0, 100) . "...\n";
    }

} catch (\Exception $e) {
    echo "❌ Ошибка импорта: " . $e->getMessage() . "\n";
    echo "Стек: " . $e->getTraceAsString() . "\n";
    exit(1);
}

