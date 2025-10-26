# Фінальне рішення: Excel експорт згідно документації pxlrbt/filament-excel

## Проблема

Раніше використовувався неправильний підхід з Laravel Excel напряму, що вимагало багато ручного коду.

## Рішення

Використано **офіційний спосіб** з документації `pxlrbt/filament-excel`:  
https://filamentphp.com/plugins/pxlrbt-excel

## Реалізація

### 1. Створено StyledTimesExport

📁 `/app/Exports/StyledTimesExport.php`

Клас розширює `ExcelExport` та додає стилі через `WithStyles`:

```php
<?php

namespace App\Exports;

use App\Models\Time;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class StyledTimesExport extends ExcelExport implements WithStyles
{
    public function setUp(): void
    {
        $this->withFilename(fn () => date('Y-m-d') . ' - Звіт_Times');
        $this->withColumns([
            Column::make('id')->heading('ID'),
            Column::make('user.name')->heading('Виконавець'),
            Column::make('title')->heading('Завдання'),
            Column::make('duration')
                ->heading('Годин')
                ->formatStateUsing(fn ($state) => number_format($state / 3600, 2, '.', '')),
            Column::make('coefficient')->heading('Коефіцієнт'),
            Column::make('calculated_amount')
                ->heading('Сума, грн')
                ->formatStateUsing(fn ($state, $record) => number_format(
                    $record->duration / 3600 * $record->coefficient * Time::PRICE,
                    2, '.', ''
                )),
            Column::make('status')
                ->heading('Статус')
                ->formatStateUsing(fn ($state) => $state ? Time::$statuses[$state] : ''),
            Column::make('report_status')
                ->heading('Статус акту')
                ->formatStateUsing(fn ($state) => $state ? Time::$reportStatuses[$state] : ''),
            Column::make('is_archived')
                ->heading('Архів')
                ->formatStateUsing(fn ($state) => $state ? 'Так' : 'Ні'),
            Column::make('created_at')
                ->heading('Створено')
                ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i')),
            Column::make('updated_at')
                ->heading('Оновлено')
                ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i')),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        return [
            // Стиль заголовків - синій фон, білий текст, товсті рамки
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => ['allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000']
                ]],
            ],
            
            // Тонкі рамки для всіх комірок
            "A1:{$highestColumn}{$highestRow}" => [
                'borders' => ['allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]],
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

### 2. Використано в TimesTable

📁 `/app/Filament/Resources/Times/Tables/TimesTable.php`

```php
use App\Exports\StyledTimesExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

// ...

->toolbarActions([
    BulkActionGroup::make([
        ExportBulkAction::make()
            ->exports([
                StyledTimesExport::make(),
            ]),
        DeleteBulkAction::make(),
    ]),
])
```

## Ключові особливості

### ✅ Метод setUp()

Використовується для конфігурації експорту (згідно документації):

```php
public function setUp(): void
{
    $this->withFilename(fn () => date('Y-m-d') . ' - Звіт_Times');
    $this->withColumns([...]);
}
```

### ✅ Column::make()

Правильне використання Column з пакету filament-excel:

```php
Column::make('duration')
    ->heading('Годин')
    ->formatStateUsing(fn ($state) => number_format($state / 3600, 2, '.', ''))
```

### ✅ WithStyles інтерфейс

Додає стилі через PhpSpreadsheet:

```php
class StyledTimesExport extends ExcelExport implements WithStyles
{
    public function styles(Worksheet $sheet): array { ... }
}
```

### ✅ ExportBulkAction

Стандартна bulk action з пакету:

```php
ExportBulkAction::make()
    ->exports([
        StyledTimesExport::make(),
    ])
```

## Переваги офіційного підходу

✅ **Менше коду** - не потрібен ручний маппінг  
✅ **Інтеграція з Filament** - автоматично працює з вибраними записами  
✅ **Підтримка черг** - можна додати `->queue()`  
✅ **Підтримка нотифікацій** - автоматичні сповіщення після експорту  
✅ **Гнучкість** - легко додавати нові експорти  
✅ **Документовано** - офіційна документація та приклади  

## Можливості розширення

### Додавання множинних експортів

```php
ExportBulkAction::make()
    ->exports([
        StyledTimesExport::make('styled')->label('З стилями'),
        ExcelExport::make('simple')->fromTable()->label('Простий'),
    ])
```

### Експорт у чергу

```php
StyledTimesExport::make()->queue()
```

### Вибіркові колонки

```php
ExcelExport::make()
    ->fromTable()
    ->except(['created_at', 'updated_at'])
```

## Результат

Excel файл тепер:
- ✅ Експортується правильно згідно документації
- ✅ Має рамки (товсті для заголовків, тонкі для даних)
- ✅ Має стилі (синій фон, білий текст, вирівнювання)
- ✅ Автоматично працює з вибраними записами
- ✅ Підтримує всі можливості пакету filament-excel

## Документація

- **Офіційна**: https://filamentphp.com/plugins/pxlrbt-excel
- **GitHub**: https://github.com/pxlrbt/filament-excel
- **Laravel Excel**: https://laravel-excel.com/

## Залежності

- ✅ `pxlrbt/filament-excel` ^3.1
- ✅ `maatwebsite/excel` (автоматично)
- ✅ `phpoffice/phpspreadsheet` (автоматично)

Готово! Тепер експорт працює правильно згідно офіційної документації! 🎉📊✨

