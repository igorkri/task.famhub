# Фінальне рішення: Excel експорт Times з рамками

## Проблема

Спроба використати `pxlrbt/filament-excel` з методами `fromQuery()`, `fileName()`, `withColumns()` призводила до помилок:
- `BadMethodCallException: Method fileName does not exist`
- `Error: Call to undefined method fromQuery()`

## Рішення

Використано **стандартний Laravel Excel** з власним класом Exporter замість пакету `pxlrbt/filament-excel`.

## Реалізація

### 1. Створено клас TimesExport

📁 `/app/Exports/TimesExport.php`

```php
<?php

namespace App\Exports;

use App\Models\Time;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TimesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query->with('user');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Виконавець',
            'Завдання',
            'Годин',
            'Коефіцієнт',
            'Сума, грн',
            'Статус',
            'Статус акту',
            'Архів',
            'Створено',
            'Оновлено',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->user->name ?? '',
            $row->title,
            number_format($row->duration / 3600, 2, '.', ''),
            $row->coefficient,
            number_format($row->duration / 3600 * $row->coefficient * Time::PRICE, 2, '.', ''),
            $row->status ? Time::$statuses[$row->status] : '',
            $row->report_status ? Time::$reportStatuses[$row->report_status] : '',
            $row->is_archived ? 'Так' : 'Ні',
            $row->created_at?->format('d.m.Y H:i'),
            $row->updated_at?->format('d.m.Y H:i'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  'B' => 20,  'C' => 50,  'D' => 12,
            'E' => 12,  'F' => 15,  'G' => 20,  'H' => 20,
            'I' => 10,  'J' => 20,  'K' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        return [
            // Стиль заголовків - синій фон, білий текст, товсті рамки
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']]],
            ],
            
            // Рамки для всіх комірок
            "A1:{$highestColumn}{$highestRow}" => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
            
            // Вирівнювання числових колонок
            "D2:D{$highestRow}" => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            "E2:E{$highestRow}" => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            "F2:F{$highestRow}" => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
        ];
    }
}
```

### 2. Інтегровано в TimesTable

📁 `/app/Filament/Resources/Times/Tables/TimesTable.php`

```php
use App\Exports\TimesExport;

// ...

->toolbarActions([
    BulkActionGroup::make([
        \Filament\Actions\Action::make('export')
            ->label('Експорт в Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function ($livewire) {
                $selectedRecords = $livewire->getSelectedTableRecords()->pluck('id');
                $query = Time::query()->whereIn('id', $selectedRecords);
                
                return \Maatwebsite\Excel\Facades\Excel::download(
                    new TimesExport($query),
                    date('Y-m-d') . ' - Звіт_Times.xlsx'
                );
            }),
        DeleteBulkAction::make(),
    ]),
])
```

## Переваги рішення

### ✅ Повний контроль над стилізацією
- Рамки (товсті для заголовків, тонкі для даних)
- Кольори (синій фон заголовків, білий текст)
- Вирівнювання (по центру, праворуч, ліворуч)
- Ширина колонок

### ✅ Правильне форматування
- Числа зберігаються як числа (можна використовувати в формулах Excel)
- Дати у зрозумілому форматі
- Українська локалізація статусів

### ✅ Оптимізація
- Eager loading (`->with('user')`)
- Експорт тільки вибраних записів
- Немає зайвих залежностей

### ✅ Професійний вигляд
- Чіткі чорні рамки
- Контрастні заголовки
- Готовий до друку

## Використання

1. У таблиці Times виберіть записи (встановіть прапорці)
2. Натисніть кнопку "Експорт в Excel" 📥
3. Файл завантажиться автоматично з назвою: `YYYY-MM-DD - Звіт_Times.xlsx`

## Результат

Excel файл містить:
- 🔲 Чорні рамки навколо всіх комірок
- 🎨 Синій фон заголовків з білим жирним текстом
- 📊 Правильно відформатовані числа та дати
- 📐 Оптимальну ширину колонок
- 🇺🇦 Українську локалізацію

## Документація

1. **Основна**: `/docs/times-excel-export.md`
2. **Стилі**: `/docs/times-excel-export-styles.md`
3. **Рамки**: `/docs/times-excel-borders.md`
4. **Візуалізація**: `/docs/times-excel-preview.md`
5. **Довідник**: `/docs/excel-styles-reference.md`

## Залежності

- ✅ `maatwebsite/excel` (встановлено через `pxlrbt/filament-excel`)
- ✅ `phpoffice/phpspreadsheet` (встановлено як залежність)
- ❌ `pxlrbt/filament-excel` (не використовується, можна видалити)

## Висновок

Використання стандартного Laravel Excel з власним класом Exporter виявилось більш гнучким та надійним рішенням, ніж спроба інтегрувати `pxlrbt/filament-excel`. Це дає повний контроль над стилізацією та форматуванням без обмежень пакету.

