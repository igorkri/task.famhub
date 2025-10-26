# Рамки (Borders) в Excel експорті Times

## Огляд

До Excel експорту Times додано професійні рамки та стилізацію через власний клас `TimesExport`.

## Додані рамки

### 1. Рамки заголовків (рядок 1)
**Тип**: Середні рамки (BORDER_MEDIUM)  
**Колір**: Чорний (#000000)

```php
'borders' => [
    'allBorders' => [
        'borderStyle' => Border::BORDER_MEDIUM,
        'color' => ['rgb' => '000000'],
    ],
],
```

### 2. Рамки для всіх комірок з даними
**Тип**: Тонкі рамки (BORDER_THIN)  
**Колір**: Чорний (#000000)

```php
"A1:{$highestColumn}{$highestRow}" => [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
],
```

## Додаткова стилізація

### Заголовки
- **Шрифт**: жирний, 12pt, білий колір
- **Фон**: синій (#4472C4)
- **Вирівнювання**: по центру (горизонтально та вертикально)

### Дані
- **Вертикальне вирівнювання**: по центру для всіх комірок
- **Числові колонки** (Годин, Коефіцієнт): по центру
- **Сума**: по правому краю
- **Ширина колонок**: автоматично встановлена для кожної колонки

## Структура файлу

### app/Exports/TimesExport.php

```php
class TimesExport implements 
    FromQuery,          // Експорт з Eloquent query
    WithHeadings,       // Заголовки колонок
    WithMapping,        // Маппінг даних
    WithStyles,         // Стилі та рамки
    WithColumnWidths    // Ширина колонок
{
    // ...
}
```

### Використання в TimesTable.php

```php
ExportBulkAction::make()
    ->label('Експорт в Excel')
    ->exports([
        ExcelExport::make()
            ->withFilename(fn () => date('Y-m-d') . ' - Звіт_Times')
            ->fromQuery(fn ($query) => new TimesExport($query)),
    ])
```

## Типи рамок (Border Styles)

Доступні стилі рамок у PhpSpreadsheet:

- `Border::BORDER_NONE` - без рамки
- `Border::BORDER_THIN` - тонка рамка (використовується для даних)
- `Border::BORDER_MEDIUM` - середня рамка (використовується для заголовків)
- `Border::BORDER_THICK` - товста рамка
- `Border::BORDER_DASHED` - штрихована
- `Border::BORDER_DOTTED` - пунктирна
- `Border::BORDER_DOUBLE` - подвійна

## Налаштування рамок

### Зміна товщини рамки

```php
'borders' => [
    'allBorders' => [
        'borderStyle' => Border::BORDER_THICK,  // Товстіша рамка
        'color' => ['rgb' => '000000'],
    ],
],
```

### Різні рамки для різних сторін

```php
'borders' => [
    'top' => [
        'borderStyle' => Border::BORDER_THICK,
        'color' => ['rgb' => '000000'],
    ],
    'bottom' => [
        'borderStyle' => Border::BORDER_THICK,
        'color' => ['rgb' => '000000'],
    ],
    'left' => [
        'borderStyle' => Border::BORDER_THIN,
        'color' => ['rgb' => 'CCCCCC'],
    ],
    'right' => [
        'borderStyle' => Border::BORDER_THIN,
        'color' => ['rgb' => 'CCCCCC'],
    ],
],
```

### Кольорові рамки

```php
'borders' => [
    'allBorders' => [
        'borderStyle' => Border::BORDER_THIN,
        'color' => ['rgb' => '4472C4'],  // Синій колір
    ],
],
```

## Результат

Excel файл тепер має:
- ✅ Чорні рамки навколо всіх комірок
- ✅ Потовщені рамки для заголовків
- ✅ Синій фон заголовків з білим текстом
- ✅ Професійний вигляд, готовий для друку
- ✅ Чітке візуальне розділення даних

## Корисні посилання

- **PhpSpreadsheet Borders**: https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
- **Laravel Excel Styling**: https://docs.laravel-excel.com/3.1/exports/styling.html

