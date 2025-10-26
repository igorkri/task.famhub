# Швидкий довідник стилів Excel для Filament

## Базові стилі через Column

### Ширина колонок
```php
Column::make('field')->width(20)
```

### Форматування тексту
```php
// Виравнювання
Column::make('field')->alignLeft()
Column::make('field')->alignCenter()
Column::make('field')->alignRight()
```

### Форматування чисел
```php
// Число
Column::make('price')->formatStateUsing(fn ($state) => floatval($state))

// Ціна з двома десятковими
Column::make('price')->formatStateUsing(fn ($state) => number_format($state, 2, '.', ''))

// Відсотки
Column::make('percentage')->formatStateUsing(fn ($state) => $state . '%')
```

### Форматування дат
```php
// Українська дата
Column::make('created_at')->getStateUsing(fn ($record) => $record->created_at?->format('d.m.Y'))

// Дата з часом
Column::make('created_at')->getStateUsing(fn ($record) => $record->created_at?->format('d.m.Y H:i:s'))

// ISO формат
Column::make('created_at')->getStateUsing(fn ($record) => $record->created_at?->toDateTimeString())
```

### Умовне форматування
```php
Column::make('status')
    ->getStateUsing(function ($record) {
        return match($record->status) {
            'active' => '✓ Активний',
            'inactive' => '✗ Неактивний',
            default => '– Невідомо',
        };
    })
```

## Розширені стилі (через WithStyles)

Для більш складних стилів потрібно створити власний клас Exporter:

```php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TimesExport implements FromCollection, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Time::with('user')->get();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // ID
            'B' => 20,  // Виконавець
            'C' => 50,  // Завдання
            'D' => 12,  // Годин
            'E' => 15,  // Сума
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Стиль заголовків (рядок 1)
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
            
            // Стиль для всіх комірок
            'A1:Z1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD'],
                    ],
                ],
            ],
        ];
    }
}
```

## Використання власного Exporter

```php
use App\Exports\TimesExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

ExportBulkAction::make()
    ->exports([
        ExcelExport::make()
            ->fromTable()
            ->export(TimesExport::class)
    ])
```

## Корисні ресурси

- **Filament Excel**: https://github.com/pxlrbt/filament-excel
- **Laravel Excel**: https://docs.laravel-excel.com/
- **PhpSpreadsheet Styles**: https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
- **PhpSpreadsheet Colors**: https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#colors

