<?php

namespace App\Imports;

use App\Models\ActOfWork;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithValidation;

class ReceiptOfFundsCsvImport implements SkipsEmptyRows, ToCollection, WithChunkReading, WithCustomCsvSettings, WithValidation
{
    private int $importedCount = 0;

    private int $skippedCount = 0;

    private array $warnings = [];

    private array $errors = [];

    public function collection(Collection $rows): void
    {
        // Предполагаем, что первая строка - заголовки
        $isFirstRow = true;
        foreach ($rows as $row) {
            if ($isFirstRow) {
                $isFirstRow = false;

                continue; // Пропускаем заголовки
            }

            try {
                $this->processRow($row);
            } catch (\Exception $e) {
                $this->errors[] = 'Помилка в рядку: '.$e->getMessage();
                $this->skippedCount++;
            }
        }
    }

    private function processRow(Collection $row): void
    {
        // Проверяем, что в строке достаточно данных
        if ($row->count() < 17) {
            $this->warnings[] = 'Рядок містить недостатньо даних: '.$row->count().' колонок';
            $this->skippedCount++;

            return;
        }

        // Извлекаем данные из CSV
        $bankId = $row[0] ?? '';
        $mfoId = $row[1] ?? '';
        $account = $row[2] ?? '';
        $currency = $row[3] ?? 'UAH';
        $operationDate = $row[4] ?? '';
        $recipientBankId = $row[6] ?? '';
        $recipientBankName = $row[7] ?? '';
        $recipientAccount = $row[8] ?? '';
        $recipientMfoId = $row[9] ?? '';
        $recipientName = $row[10] ?? '';
        $documentNumber = $row[11] ?? '';
        $documentDate = $row[12] ?? '';
        $amount = $row[14] ?? 0;
        $purpose = $row[15] ?? '';
        $amountDuplicate = $row[16] ?? 0;

        // Валидация и очистка данных
        $amount = $this->parseAmount($amount);
        if ($amount <= 0) {
            $this->warnings[] = "Пропущено запис з нульовою або від'ємною сумою: {$amount}";
            $this->skippedCount++;

            return;
        }

        $operationDate = $this->parseDate($operationDate);
        if (! $operationDate) {
            $this->errors[] = "Невірна дата операції: {$row[4]}";
            $this->skippedCount++;

            return;
        }

        $documentDate = $this->parseDate($documentDate);

        // Проверяем, не существует ли уже такая запись
        $exists = ActOfWork::where('type', ActOfWork::TYPE_RECEIPT_OF_FUNDS)
            ->where('number', $documentNumber)
            ->whereDate('date', $operationDate->format('Y-m-d'))
            ->where('paid_amount', $amount)
            ->exists();

        if ($exists) {
            $this->warnings[] = "Запис вже існує: документ {$documentNumber} від {$operationDate->format('d.m.Y')} на суму {$amount}";
            $this->skippedCount++;

            return;
        }

        // Получаем пользователя по умолчанию (первого пользователя)
        $user = User::first();
        if (! $user) {
            $this->errors[] = 'Не знайдено користувача для призначення запису';
            $this->skippedCount++;

            return;
        }

        // Создаем запись
        $actOfWork = ActOfWork::create([
            'number' => $documentNumber ?: 'AUTO-'.uniqid(),
            'status' => ActOfWork::STATUS_PENDING,
            'type' => ActOfWork::TYPE_RECEIPT_OF_FUNDS,
            'period' => [
                'type' => 'month',
                'year' => $operationDate->year,
                'month' => $operationDate->format('F'),
            ],
            'period_type' => 'month',
            'period_year' => $operationDate->year,
            'period_month' => $operationDate->format('F'),
            'user_id' => $user->id,
            'date' => $operationDate,
            'description' => $this->buildDescription($recipientName, $purpose, $recipientBankName),
            'total_amount' => 0.00,
            'paid_amount' => $amount,
            'sort' => 0,
        ]);

        $this->importedCount++;

        Log::info('Імпортовано запис про надходження коштів', [
            'id' => $actOfWork->id,
            'number' => $documentNumber,
            'amount' => $amount,
            'date' => $operationDate->format('Y-m-d'),
        ]);
    }

    private function parseAmount(string $amount): float
    {
        // Удаляем пробелы и заменяем запятую на точку
        $amount = str_replace([' ', ','], ['', '.'], trim($amount));

        return (float) $amount;
    }

    private function parseDate(string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Пробуем различные форматы даты
            $formats = [
                'd.m.Y H:i:s',  // 15.10.2025 14:10:55
                'd.m.Y',        // 15.10.2025
                'Y-m-d H:i:s',  // 2025-10-15 14:10:55
                'Y-m-d',        // 2025-10-15
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $date);
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Если ничего не подошло, пробуем стандартный парсинг
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function buildDescription(string $recipientName, string $purpose, string $bankName): string
    {
        $parts = [];

        if (! empty($recipientName)) {
            $parts[] = 'Від: '.trim(str_replace(['"', "'"], '', $recipientName));
        }

        //        if (! empty($bankName)) {
        //            $parts[] = 'Банк: '.trim(str_replace(['"', "'"], '', $bankName));
        //        }

        if (! empty($purpose)) {
            $parts[] = 'Призначення: '.trim($purpose);
        }

        return implode('. ', $parts) ?: 'Надходження коштів';
    }

    public function rules(): array
    {
        return [
            //            // Базовая валидация для первых колонок
            //            '0' => 'nullable|string',  // МФО ID банку
            //            '1' => 'nullable|string',  // МФО ID
            //            '2' => 'nullable|string',  // Рахунок
            //            '3' => 'nullable|string',  // Валюта
            //            '4' => 'required|string',  // Дата операції
            //            '14' => 'required|numeric|min:0.01',  // Сума
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '4.required' => 'Дата операції обов\'язкова',
            '14.required' => 'Сума обов\'язкова',
            '14.numeric' => 'Сума має бути числом',
            '14.min' => 'Сума має бути більше 0',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'Windows-1251',
            'delimiter' => ';',
            'enclosure' => '"',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
