# Excel експорт для таблиці Times

## Огляд

Експорт вибраних записів Times у файл Excel з рамками та стилізацією через пакет `pxlrbt/filament-excel`.

## Швидкий старт

1. Виберіть записи в таблиці Times (встановіть прапорці)
2. Натисніть кнопку **"Export"** у панелі масових дій
3. Файл `YYYY-MM-DD - Звіт_Times.xlsx` завантажиться автоматично

## Що експортується

- **Назва задачі** - назва завдання
- **Проект** - назва проекту до якого належить задача
- **Час** - тривалість у годинах (конвертується з секунд)
- **Коефіцієнт** - множник для розрахунку
- **Ціна** - розрахована сума (час × коефіцієнт × базова ціна)
- **Коментар** - коментар до запису часу
- **Дата модифікації таксу** - дата останнього оновлення
- **Посилання на задачу** - кликабельне посилання для переходу до задачі в Asana (генерується з gid)

**Примітка**: Посилання на задачу автоматично генерується з поля `gid` в форматі `https://app.asana.com/0/0/{gid}`. У Excel це кликабельне посилання з текстом "Відкрити задачу" синього кольору з підкресленням. Якщо у задачі немає `gid` (не синхронізована з Asana), посилання буде порожнім.

## Технічна реалізація

### Файли

**Export клас**: `/app/Exports/StyledTimesExport.php`  
**Таблиця**: `/app/Filament/Resources/Times/Tables/TimesTable.php`

### StyledTimesExport

```php
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Maatwebsite\Excel\Concerns\WithStyles;

class StyledTimesExport extends ExcelExport implements WithStyles
{
    public function setUp(): void
    {
        $this->withFilename(fn () => date('Y-m-d') . ' - Звіт_Times');
        $this->withColumns([...]);
    }
    
    public function styles(Worksheet $sheet): array
    {
        // Рамки та стилі
    }
}
```

### Використання в таблиці

```php
use App\Exports\StyledTimesExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

->toolbarActions([
    BulkActionGroup::make([
        ExportBulkAction::make()
            ->exports([
                StyledTimesExport::make(),
            ]),
    ]),
])
```

## Стилізація

### Рамки
- **Заголовки**: товсті чорні рамки (`BORDER_MEDIUM`)
- **Дані**: тонкі чорні рамки (`BORDER_THIN`)

### Кольори
- **Заголовки**: синій фон (#4472C4), білий текст
- **Шрифт**: жирний, 12pt

### Вирівнювання
- **Заголовки**: по центру
- **Числа**: по центру
- **Сума**: праворуч

## Форматування даних

### Числа
```php
Column::make('duration')
    ->formatStateUsing(fn ($state) => number_format($state / 3600, 2, '.', ''))
```

### Дати
```php
Column::make('created_at')
    ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i'))
```

### Статуси
```php
Column::make('status')
    ->formatStateUsing(fn ($state) => $state ? Time::$statuses[$state] : '')
```

## Додаткові можливості

### Експорт усіх записів

Додайте `ExportAction` до header actions:

```php
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

->headerActions([
    ExportAction::make()
        ->exports([StyledTimesExport::make()]),
])
```

### Експорт у чергу

Для великих обсягів даних:

```php
StyledTimesExport::make()->queue()
```

### Множинні формати експорту

```php
ExportBulkAction::make()
    ->exports([
        StyledTimesExport::make('styled')->label('З стилями'),
        ExcelExport::make('simple')->fromTable()->label('Простий'),
    ])
```

## Залежності

- `pxlrbt/filament-excel` ^3.1
- `maatwebsite/excel` (автоматично)
- `phpoffice/phpspreadsheet` (автоматично)

## Документація

- **Офіційна документація**: https://filamentphp.com/plugins/pxlrbt-excel
- **GitHub**: https://github.com/pxlrbt/filament-excel
- **Laravel Excel**: https://laravel-excel.com/

