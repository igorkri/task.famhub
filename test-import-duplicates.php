<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Imports\ReceiptOfFundsCsvImport;
use App\Models\ActOfWork;
use Maatwebsite\Excel\Facades\Excel;

// Путь к тестовому файлу
$filePath = __DIR__.'/docs/export.csv';

if (!file_exists($filePath)) {
    echo "❌ Файл не знайдено: {$filePath}\n";
    exit(1);
}

echo "🔍 Перевірка дублікатів перед імпортом\n";
echo "======================================\n\n";

// Підрахунок записів до імпорту
$countBefore = ActOfWork::where('type', ActOfWork::TYPE_RECEIPT_OF_FUNDS)->count();
echo "📊 Записів до імпорту: {$countBefore}\n\n";

// Перший імпорт
echo "📥 Перший імпорт...\n";
try {
    $import1 = new ReceiptOfFundsCsvImport;
    Excel::import($import1, $filePath);

    echo "✅ Перший імпорт завершено!\n";
    echo "  • Імпортовано: {$import1->getImportedCount()}\n";
    echo "  • Пропущено: {$import1->getSkippedCount()}\n\n";

    if ($import1->getErrors()) {
        echo "❌ Помилки:\n";
        foreach (array_slice($import1->getErrors(), 0, 3) as $error) {
            echo "  - {$error}\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "❌ Помилка: " . $e->getMessage() . "\n";
    exit(1);
}

$countAfterFirst = ActOfWork::where('type', ActOfWork::TYPE_RECEIPT_OF_FUNDS)->count();
echo "📊 Записів після першого імпорту: {$countAfterFirst}\n";
echo "   Додано нових: " . ($countAfterFirst - $countBefore) . "\n\n";

// Другий імпорт (має пропустити всі записи як дублікати)
echo "📥 Другий імпорт (перевірка дублікатів)...\n";
try {
    $import2 = new ReceiptOfFundsCsvImport;
    Excel::import($import2, $filePath);

    echo "✅ Другий імпорт завершено!\n";
    echo "  • Імпортовано: {$import2->getImportedCount()}\n";
    echo "  • Пропущено: {$import2->getSkippedCount()}\n\n";

    if ($import2->getWarnings()) {
        echo "⚠️  Попередження (перші 5):\n";
        foreach (array_slice($import2->getWarnings(), 0, 5) as $warning) {
            echo "  - {$warning}\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "❌ Помилка: " . $e->getMessage() . "\n";
    exit(1);
}

$countAfterSecond = ActOfWork::where('type', ActOfWork::TYPE_RECEIPT_OF_FUNDS)->count();
echo "📊 Записів після другого імпорту: {$countAfterSecond}\n";
echo "   Додано нових: " . ($countAfterSecond - $countAfterFirst) . "\n\n";

// Результат
if ($countAfterSecond == $countAfterFirst) {
    echo "✅ УСПІХ! Дублікати не створюються.\n";
    echo "   Всі записи з другого імпорту були правильно пропущені.\n";
} else {
    echo "❌ УВАГА! Виявлено створення дублікатів.\n";
    echo "   Додано записів при повторному імпорті: " . ($countAfterSecond - $countAfterFirst) . "\n";
}

// Показуємо останній імпортований запис
echo "\n📝 Останній запис:\n";
$last = ActOfWork::where('type', ActOfWork::TYPE_RECEIPT_OF_FUNDS)->latest()->first();
if ($last) {
    echo "  ID: {$last->id}\n";
    echo "  Номер: {$last->number}\n";
    echo "  Дата: {$last->date->format('d.m.Y')}\n";
    echo "  total_amount: {$last->total_amount}\n";
    echo "  paid_amount: {$last->paid_amount}\n";
    echo "  Опис: " . mb_substr($last->description, 0, 80) . "...\n";
}

