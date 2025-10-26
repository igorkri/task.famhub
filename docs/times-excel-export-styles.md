# Стилізація Excel експорту для Times

## Огляд

До Excel експорту додано стилізацію для покращення читабельності та професійного вигляду звітів.

## Додані стилі

### 1. Ширина колонок (`->width()`)

Кожна колонка має визначену ширину для оптимального відображення:

```php
Column::make('id')->heading('ID')->width(10),
Column::make('user.name')->heading('Виконавець')->width(20),
Column::make('title')->heading('Завдання')->width(50),
Column::make('duration')->heading('Годин')->width(12),
Column::make('coefficient')->heading('Коефіцієнт')->width(12),
Column::make('calculated_amount')->heading('Сума, грн')->width(15),
Column::make('status')->heading('Статус')->width(20),
Column::make('report_status')->heading('Статус акту')->width(20),
Column::make('is_archived')->heading('Архів')->width(10),
Column::make('created_at')->heading('Створено')->width(20),
Column::make('updated_at')->heading('Оновлено')->width(20),
```

### 2. Форматування чисел

Числові поля конвертуються у правильний формат для Excel:

```php
// Годин - конвертація з секунд у години
Column::make('duration')
    ->getStateUsing(fn ($record) => number_format($record->duration / 3600, 2))
    ->formatStateUsing(fn ($state) => floatval($state)),

// Коефіцієнт - як число
Column::make('coefficient')
    ->formatStateUsing(fn ($state) => floatval($state)),

// Сума - розрахунок та форматування
Column::make('calculated_amount')
    ->getStateUsing(fn ($record) => $record->duration / 3600 * $record->coefficient * Time::PRICE)
    ->formatStateUsing(fn ($state) => floatval($state)),
```

### 3. Форматування дат

Дати відображаються у зручному форматі `d.m.Y H:i`:

```php
Column::make('created_at')
    ->getStateUsing(fn ($record) => $record->created_at?->format('d.m.Y H:i')),

Column::make('updated_at')
    ->getStateUsing(fn ($record) => $record->updated_at?->format('d.m.Y H:i')),
```

### 4. Перетворення булевих значень

Поле `is_archived` відображається як "Так" або "Ні":

```php
Column::make('is_archived')
    ->getStateUsing(fn ($record) => $record->is_archived ? 'Так' : 'Ні'),
```

### 5. Оптимізація запитів

Додано eager loading для уникнення N+1 проблеми:

```php
->modifyQueryUsing(fn ($query) => $query->with('user'))
```

## Результат

Excel файл тепер має:
- ✅ Правильну ширину колонок
- ✅ Числові значення як числа (не текст)
- ✅ Відформатовані дати
- ✅ Зрозумілі українські значення для статусів та булевих полів
- ✅ Оптимізовані запити до БД

## Розширення стилів

Якщо потрібні додаткові стилі (кольори, жирний текст, рамки), можна створити власний клас Exporter, який розширює можливості PhpSpreadsheet. Приклад:

```php
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimesExport implements WithStyles
{
    public function styles(Worksheet $sheet)
    {
        return [
            // Стилі для заголовків (перший рядок)
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
```

Більше інформації: https://docs.laravel-excel.com/3.1/exports/styling.html

