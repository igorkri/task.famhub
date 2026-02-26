<?php

namespace App\Imports;

use App\Models\Contractor;
use App\Models\ContractorActOfCompletedWork;
use App\Models\ContractorActOfCompletedWorkItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use NumberToWords\NumberToWords;

class PaymentsToContractorActsImport implements SkipsEmptyRows, ToCollection, WithCustomCsvSettings
{
    private int $importedCount = 0;

    private int $skippedCount = 0;

    private array $warnings = [];

    private array $errors = [];

    /**
     * Очікуваний формат CSV / Excel (як у файлі UAH_PAYMENTS_TOV_"INHSOT"_28012026.csv):
     *
     * Дата документа,Група,Номер документа,Сума,Назва відправника,Призначення платежу
     * Рядки з однаковою Групою об'єднуються в один акт (дата = остання в групі, сума = сума по групі,
     * номер акту = номер групи, номери документів — в поле description через кому).
     */
    private const COL_DOC_DATE = 0;

    private const COL_GROUP = 1;

    private const COL_DOC_NUMBER = 2;

    private const COL_AMOUNT = 3;

    private const COL_CUSTOMER_NAME = 4;

    private const COL_PURPOSE = 5;

    private const MIN_COLUMNS = 6;

    public function collection(Collection $rows): void
    {
        $isFirst = true;
        $grouped = [];

        foreach ($rows as $row) {
            $row = $row instanceof Collection ? $row : collect($row);

            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $groupKey = trim((string) ($row[self::COL_GROUP] ?? ''));
            if ($groupKey === '') {
                $this->warnings[] = 'Рядок пропущено: порожня група';
                $this->skippedCount++;
                continue;
            }

            if ($row->count() < self::MIN_COLUMNS) {
                $this->warnings[] = 'Рядок пропущено: замало колонок ('.$row->count().')';
                $this->skippedCount++;
                continue;
            }

            $docDate = $this->parseDate((string) ($row[self::COL_DOC_DATE] ?? ''));
            if (! $docDate) {
                $this->warnings[] = 'Рядок пропущено: невірна дата документа';
                $this->skippedCount++;
                continue;
            }

            $amount = $this->parseAmount((string) ($row[self::COL_AMOUNT] ?? ''));
            if ($amount <= 0) {
                $this->warnings[] = 'Рядок пропущено: сума ≤ 0';
                $this->skippedCount++;
                continue;
            }

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [];
            }
            $grouped[$groupKey][] = [
                'doc_date' => $docDate,
                'doc_number' => trim((string) ($row[self::COL_DOC_NUMBER] ?? '')),
                'amount' => $amount,
                'customer_name' => trim(str_replace(['"', "'"], '', (string) ($row[self::COL_CUSTOMER_NAME] ?? ''))),
                'purpose' => trim((string) ($row[self::COL_PURPOSE] ?? '')),
            ];
        }

        foreach ($grouped as $groupNumber => $groupRows) {
            try {
                $this->processGroup($groupNumber, collect($groupRows));
            } catch (\Throwable $e) {
                $this->errors[] = "Група {$groupNumber}: ".$e->getMessage();
                $this->skippedCount++;
            }
        }
    }

    /**
     * Створює один акт на групу: дата = остання в групі, сума = сума по групі,
     * номер акту = номер групи, номери документів — в description через кому.
     */
    private function processGroup(string $groupNumber, Collection $groupRows): void
    {
        $lastDate = $groupRows->max('doc_date');
        $totalAmount = $groupRows->sum('amount');
        $docNumbers = $groupRows->pluck('doc_number')->filter()->unique()->values()->all();
        $docNumbersString = implode(', ', $docNumbers);

        $firstRow = $groupRows->first();
        $customerName = $firstRow['customer_name'];
        $purpose = $firstRow['purpose'] ?: 'Оплата за послуги';

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

        $agreementNumber = $groupNumber;
        $agreementDate = $lastDate;
        if ($customer && $customer->dogovor) {
            $dogovor = $customer->dogovor;
            $agreementNumber = $dogovor['number'] ?? $groupNumber;
            if (! empty($dogovor['date'])) {
                try {
                    $agreementDate = Carbon::parse($dogovor['date']);
                } catch (\Throwable) {
                    $agreementDate = $lastDate;
                }
            }
        }

        $exists = ContractorActOfCompletedWork::where('number', $groupNumber)
            ->whereDate('date', $lastDate->format('Y-m-d'))
            ->where('total_amount', $totalAmount)
            ->exists();

        if ($exists) {
            $this->warnings[] = "Акт групи вже існує: {$groupNumber} від {$lastDate->format('d.m.Y')}, {$totalAmount} грн";
            $this->skippedCount++;

            return;
        }

        $amountInWords = '';
        try {
            $amountInKopiyky = (int) round($totalAmount * 100);
            $amountInWords = NumberToWords::transformCurrency('ua', $amountInKopiyky, 'UAH');
        } catch (\Throwable) {
            // ігноруємо
        }

        $descriptionParts = [];
        if ($docNumbersString !== '') {
            $descriptionParts[] = 'Номери документів: '.$docNumbersString;
        }
        if ($purpose !== 'Оплата за послуги') {
            $descriptionParts[] = $purpose;
        }
        $description = implode("\n", $descriptionParts) ?: null;

        $act = ContractorActOfCompletedWork::create([
            'number' => $groupNumber,
            'date' => $lastDate,
            'place_of_compilation' => $myCompany->requisites['act_place'] ?? $myCompany->requisites['physical_address'] ?? null,
            'contractor_id' => $myCompany->id,
            'customer_id' => $customerId,
            'agreement_number' => $agreementNumber,
            'agreement_date' => $agreementDate,
            'customer_data' => $customerData,
            'total_amount' => $totalAmount,
            'vat_amount' => 0.0,
            'total_with_vat' => $totalAmount,
            'total_amount_in_words' => $amountInWords ?: null,
            'description' => $description,
            'status' => ContractorActOfCompletedWork::STATUS_DRAFT,
            'sort' => 0,
        ]);

        ContractorActOfCompletedWorkItem::create([
            'contractor_act_of_completed_work_id' => $act->id,
            'sequence_number' => 1,
            'service_description' => $purpose,
            'unit' => 'Послуга',
            'quantity' => 1,
            'unit_price' => $totalAmount,
            'amount' => $totalAmount,
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
