# Метод setUp() в TimesExport

## Огляд

Метод `setUp()` дозволяє налаштовувати експорт за замовчуванням, встановлюючи назву файлу та колонки, які будуть експортуватись.

## Використання

### Базовий приклад

```php
public function setUp(): void
{
    $this->withFilename(date('Y-m-d') . ' - Звіт_Times');
    $this->withColumns([
        'id' => 'ID',
        'user.name' => 'Виконавець',
        'title' => 'Завдання',
        'duration' => 'Годин',
        'coefficient' => 'Коефіцієнт',
        'calculated_amount' => 'Сума, грн',
        'status' => 'Статус',
        'report_status' => 'Статус акту',
        'is_archived' => 'Архів',
        'created_at' => 'Створено',
        'updated_at' => 'Оновлено',
    ]);
}
```

## Методи

### withFilename(string $filename)

Встановлює назву файлу (без розширення).

```php
$this->withFilename('custom_export');
// Результат: custom_export.xlsx

$this->withFilename(date('Y-m-d') . ' - Звіт');
// Результат: 2025-10-26 - Звіт.xlsx
```

### withColumns(array $columns)

Встановлює список колонок для експорту.

```php
$this->withColumns([
    'id' => 'ID',
    'name' => 'Ім\'я',
    'email' => 'Email',
]);
```

**Ключі масиву** - це ідентифікатори полів (використовуються для маппінгу).  
**Значення масиву** - це заголовки колонок у Excel файлі.

### getFilename()

Отримує поточну назву файлу.

```php
$export = new TimesExport($query);
$filename = $export->getFilename(); // '2025-10-26 - Звіт_Times'
```

## Архітектура

### Властивості класу

```php
protected $query;          // Query для отримання даних
protected $filename;       // Назва файлу без розширення
protected $columns = [];   // Масив колонок: ключ => заголовок
```

### Потік виконання

```
1. new TimesExport($query)
   ↓
2. __construct($query)
   ↓
3. setUp()  ← Тут встановлюються filename та columns
   ↓
4. withFilename('...')
   ↓
5. withColumns([...])
   ↓
6. Excel::download($export, $export->getFilename() . '.xlsx')
```

## Приклади налаштування

### Приклад 1: Простий експорт

```php
public function setUp(): void
{
    $this->withFilename('simple_export');
    $this->withColumns([
        'id' => 'ID',
        'name' => 'Name',
    ]);
}
```

### Приклад 2: Експорт з датою

```php
public function setUp(): void
{
    $this->withFilename(date('Y-m-d_H-i-s') . '_report');
    $this->withColumns([
        'id' => 'ID',
        'created_at' => 'Created Date',
    ]);
}
```

### Приклад 3: Вибіркові колонки

```php
public function setUp(): void
{
    $this->withFilename('users_export');
    $this->withColumns([
        'id' => 'User ID',
        'user.name' => 'Full Name',
        'user.email' => 'Email Address',
    ]);
}
```

### Приклад 4: Багатомовний експорт

```php
public function setUp(): void
{
    $locale = app()->getLocale();
    
    $this->withFilename(date('Y-m-d') . ' - ' . __('reports.times'));
    $this->withColumns([
        'id' => __('fields.id'),
        'user.name' => __('fields.executor'),
        'title' => __('fields.task'),
        // ...
    ]);
}
```

## Переваги методу setUp()

✅ **Централізована конфігурація** - всі налаштування в одному місці  
✅ **Легко змінювати** - можна швидко змінити назву файлу або колонки  
✅ **Підтримка методів ланцюжка** - `withFilename()` та `withColumns()` повертають `$this`  
✅ **Гнучкість** - можна використовувати динамічні значення (дата, locale, тощо)  

## Розширення

Ви можете додати інші методи налаштування:

```php
public function withSheetName(string $sheetName): self
{
    $this->sheetName = $sheetName;
    return $this;
}

public function withDateFormat(string $format): self
{
    $this->dateFormat = $format;
    return $this;
}

public function setUp(): void
{
    $this->withFilename('report')
        ->withColumns([...])
        ->withSheetName('Times Data')
        ->withDateFormat('d.m.Y');
}
```

## Використання в Filament

```php
$export = new TimesExport($query);

return Excel::download(
    $export,
    $export->getFilename() . '.xlsx'  // Використовуємо getFilename()
);
```

## Тестування

```php
$export = new TimesExport($query);

// Перевірка назви файлу
$this->assertEquals('2025-10-26 - Звіт_Times', $export->getFilename());

// Перевірка заголовків
$headings = $export->headings();
$this->assertContains('ID', $headings);
$this->assertContains('Виконавець', $headings);
```

## Підсумок

Метод `setUp()` забезпечує чистий та зрозумілий спосіб налаштування експорту. Всі конфігурації централізовані, легко читаються та можуть бути динамічними.

