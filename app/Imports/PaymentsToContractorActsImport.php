<?php

namespace App\Imports;

use App\Models\Contractor;
use App\Models\ContractorActOfCompletedWork;
use App\Models\ContractorActOfCompletedWorkItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use NumberToWords\NumberToWords;

class PaymentsToContractorActsImport implements SkipsEmptyRows, ToCollection, WithChunkReading, WithCustomCsvSettings
{
    private int $importedCount = 0;

    private int $skippedCount = 0;

    private array $warnings = [];

    private array $errors = [];

    /**
     * Очікуваний формат CSV / Excel (як у файлі UAH_PAYMENTS_TOV_"INHSOT"_28012026.csv):
     *
     * Дата документа,Номер документа,Сума,Назва відправника,Призначення платежу
     * 18.08.2025,242,18000.00,"ТОВ ""ІНГСОТ"" ","За підтримку та доопрацювання веб-сайту ... "
     */
    private const COL_DOC_DATE = 0;

    private const COL_DOC_NUMBER = 1;

    private const COL_AMOUNT = 2;

    private const COL_CUSTOMER_NAME = 3;

    private const COL_PURPOSE = 4;

    private const MIN_COLUMNS = 5;

    public function collection(Collection $rows): void
    {
        $isFirst = true;

        foreach ($rows as $row) {
            $row = $row instanceof Collection ? $row : collect($row);

            // Перша строка - заголовки
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            try {
                $this->processRow($row);
            } catch (\Throwable $e) {
                $this->errors[] = 'Рядок: '.$e->getMessage();
                $this->skippedCount++;
            }
        }
    }

    private function processRow(Collection $row): void
    {
        if ($row->count() < self::MIN_COLUMNS) {
            $this->warnings[] = 'Рядок пропущено: замало колонок ('.$row->count().')';
            $this->skippedCount++;

            return;
        }

        $docDate = $this->parseDate((string) ($row[self::COL_DOC_DATE] ?? ''));
        if (! $docDate) {
            $this->warnings[] = 'Рядок пропущено: невірна дата документа';
            $this->skippedCount++;

            return;
        }

        $amount = $this->parseAmount((string) ($row[self::COL_AMOUNT] ?? ''));
        if ($amount <= 0) {
            $this->warnings[] = 'Рядок пропущено: сума ≤ 0';
            $this->skippedCount++;

            return;
        }

        $customerName = trim(str_replace(['"', "'"], '', (string) ($row[self::COL_CUSTOMER_NAME] ?? '')));
        $documentNumber = trim((string) ($row[self::COL_DOC_NUMBER] ?? ''));
        $documentDate = $docDate;
        $purpose = trim((string) ($row[self::COL_PURPOSE] ?? ''));

        $myCompany = Contractor::myCompany()->first();
        if (! $myCompany) {
            $this->errors[] = 'Не знайдено "мою компанію" в contractors';
            $this->skippedCount++;

            return;
        }

        $customer = $this->findCustomerByName($customerName);
        $customerId = $customer?->id;
        if ($customer) {
            $req = $customer->requisites ?? [];
            $customerData = [
                'name' => $req['name'] ?? $customer->full_name ?? $customer->name,
                'director' => $req['director'] ?? $customer->in_the_person_of,
                'identification_code' => $req['identification_code'] ?? null,
                'vat_certificate' => $req['vat_certificate'] ?? null,
                'individual_tax_number' => $req['individual_tax_number'] ?? $req['identification_code'] ?? null,
                'bank_name' => $req['bank_name'] ?? null,
                'mfo' => $req['mfo'] ?? null,
                'iban' => $req['iban'] ?? null,
                'address' => trim(($req['legal_address'] ?? '') . "\n" . ($req['physical_address'] ?? '')),
            ];
        } else {
            $customerData = ['name' => $customerName ?: '—'];
        }

        $agreementNumber = null;
        $agreementDate = null;

        if ($customer && $customer->dogovor) {
            $dogovor = $customer->dogovor;
            $agreementNumber = $dogovor['number'] ?? null;

            if (! empty($dogovor['date'])) {
                try {
                    $agreementDate = Carbon::parse($dogovor['date']);
                } catch (\Throwable) {
                    $agreementDate = null;
                }
            }
        }

        if (! $agreementNumber) {
            $agreementNumber = $documentNumber ?: null;
        }

        if (! $agreementDate) {
            $agreementDate = $documentDate;
        }

        $exists = false;
        if ($documentNumber !== '') {
            $exists = ContractorActOfCompletedWork::where('number', $documentNumber)
                ->whereDate('date', $docDate->format('Y-m-d'))
                ->where('total_amount', $amount)
                ->exists();
        }

        if ($exists) {
            $this->warnings[] = "Запис вже існує: {$documentNumber} від {$docDate->format('d.m.Y')}, {$amount} грн";
            $this->skippedCount++;

            return;
        }

        $vatAmount = 0.0;
        $totalWithVat = $amount;
        $amountInWords = '';

        try {
            $amountInKopiyky = (int) round($amount * 100);
            $amountInWords = NumberToWords::transformCurrency('ua', $amountInKopiyky, 'UAH');
        } catch (\Throwable) {
            // ігноруємо, залишаємо порожнім
        }

        $act = ContractorActOfCompletedWork::create([
            'number' => $documentNumber ?: ('AUTO-'.now()->format('Ymd').'-'.str_pad((string) (ContractorActOfCompletedWork::whereDate('date', $docDate)->count() + 1), 3, '0', STR_PAD_LEFT)),
            'date' => $docDate,
            'place_of_compilation' => $myCompany->requisites['act_place'] ?? $myCompany->requisites['physical_address'] ?? null,
            'contractor_id' => $myCompany->id,
            'customer_id' => $customerId,
            'agreement_number' => $agreementNumber,
            'agreement_date' => $agreementDate,
            'customer_data' => $customerData,
            'total_amount' => $amount,
            'vat_amount' => $vatAmount,
            'total_with_vat' => $totalWithVat,
            'total_amount_in_words' => $amountInWords ?: null,
            'description' => $purpose ?: null,
            'status' => ContractorActOfCompletedWork::STATUS_DRAFT,
            'sort' => 0,
        ]);

        ContractorActOfCompletedWorkItem::create([
            'contractor_act_of_completed_work_id' => $act->id,
            'sequence_number' => 1,
            'service_description' => $purpose ?: 'Оплата за послуги',
            'unit' => 'Послуга',
            'quantity' => 1,
            'unit_price' => $amount,
            'amount' => $amount,
            'sort' => 0,
        ]);

        $this->importedCount++;
    }

    private function findCustomerByName(string $name): ?Contractor
    {
        if ($name === '') {
            return null;
        }

        $normalize = function ($s): string {
            $s = mb_strtolower((string) $s);
            $s = str_replace(
                ['"', "'", '«', '»', '“', '”', '`'],
                '',
                $s,
            );

            return preg_replace('/\s+/', ' ', trim($s));
        };

        $search = $normalize($name);

        if ($search === '') {
            return null;
        }

        return Contractor::where('my_company', false)
            ->get()
            ->first(function (Contractor $c) use ($search, $normalize) {
                $n = $normalize($c->name);
                $f = $normalize($c->full_name ?? '');

                if ($n === '' && $f === '') {
                    return false;
                }

                return str_contains($n, $search)
                    || str_contains($search, $n)
                    || ($f !== '' && (str_contains($f, $search) || str_contains($search, $f)));
            });
    }

    private function parseAmount(string $value): float
    {
        $value = str_replace(["\xC2\xA0", ' ', ',', ' грн.', 'грн'], ['', '', '.', '', ''], trim($value));

        return (float) $value;
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['d.m.Y H:i:s', 'd.m.Y', 'Y-m-d H:i:s', 'Y-m-d', 'd/m/Y', 'Y-m-d\TH:i:s'];
        foreach ($formats as $format) {
            try {
                $d = Carbon::createFromFormat($format, $value);

                return $d;
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'UTF-8',
            'delimiter' => ',',
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
