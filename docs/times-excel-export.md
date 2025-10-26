# Експорт часу (Times) у Excel

## Огляд

У таблиці Times додано можливість експорту вибраних записів у файл Excel за допомогою пакету `pxlrbt/filament-excel`.

## Використання

### 1. Виберіть записи для експорту

У таблиці Times виберіть один або декілька записів, встановивши прапорці навпроти потрібних рядків.

### 2. Натисніть кнопку "Експорт у Excel"

Після вибору записів натисніть кнопку **"Експорт у Excel"** у панелі масових дій (вгорі таблиці).

### 3. Завантажте файл

Файл Excel буде автоматично завантажено на ваш комп'ютер. Він міститиме всі видимі колонки з вибраних записів:

- Виконавець (user.name)
- Завдання (title)
- Годин (duration)
- Коефіцієнт (coefficient)
- Сума, грн (calculated_amount)
- Статус (status)
- Статус акту (report_status)
- Архів (is_archived)
- Created at
- Updated at

## Технічні деталі

### Пакет
- **Назва**: `pxlrbt/filament-excel`
- **Версія**: ^3.1
- **Документація**: https://github.com/pxlrbt/filament-excel

### Файли
- **Таблиця**: `app/Filament/Resources/Times/Tables/TimesTable.php`
- **Ресурс**: `app/Filament/Resources/Times/TimeResource.php`

### Код

```php
use App\Exports\TimesExport;
use Maatwebsite\Excel\Facades\Excel;

// У методі configure()
->toolbarActions([
    BulkActionGroup::make([
        \Filament\Actions\Action::make('export')
            ->label('Експорт в Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function ($livewire) {
                $selectedRecords = $livewire->getSelectedTableRecords()->pluck('id');
                $query = Time::query()->whereIn('id', $selectedRecords);
                
                return Excel::download(
                    new TimesExport($query),
                    date('Y-m-d') . ' - Звіт_Times.xlsx'
                );
            }),
        DeleteBulkAction::make(),
    ]),
])
```

## Клас TimesExport

Створено власний клас `/app/Exports/TimesExport.php` який реалізує:

- **FromQuery** - експорт з Eloquent query
- **WithHeadings** - заголовки колонок  
- **WithMapping** - маппінг даних з моделі
- **WithStyles** - стилізація, рамки, кольори
- **WithColumnWidths** - ширина колонок

```php
class TimesExport implements 
    FromQuery, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithColumnWidths
{
    // ...методи для налаштування експорту
}
```

## Застосовані стилі

Всі стилі налаштовуються в класі `TimesExport`:

### Рамки (Borders)
- **Заголовки**: товсті чорні рамки (`BORDER_MEDIUM`)
- **Дані**: тонкі чорні рамки (`BORDER_THIN`) навколо кожної комірки

### Кольори
- **Фон заголовків**: синій (#4472C4)
- **Текст заголовків**: білий (#FFFFFF), жирний, 12pt

### Автоматична ширина колонок
Оптимальна ширина для кожної колонки:
- ID: 10 символів
- Виконавець: 20 символів
- Завдання: 50 символів
- Числові поля: 12-15 символів
- Дати: 20 символів

### Форматування даних
- **Годин**: конвертується з секунд у години з форматом `0.00`
- **Коефіцієнт**: зберігається як число (не текст)
- **Сума**: обчислюється та зберігається як число для подальших розрахунків в Excel
- **Дати**: формат `d.m.Y H:i` (наприклад: `26.10.2025 14:30`)
- **Архів**: "Так" / "Ні"
- **Статуси**: українською мовою

### Вирівнювання
- **Заголовки**: по центру (горизонтально та вертикально)
- **Числа (Годин, Коефіцієнт)**: по центру
- **Сума**: по правому краю
- **Всі комірки**: по центру вертикально

### Оптимізація
- `->with('user')` в query - eager loading для уникнення N+1 проблеми

Детальніше про стилі: 
- [docs/times-excel-export-styles.md](times-excel-export-styles.md)
- [docs/times-excel-borders.md](times-excel-borders.md)
- [docs/times-excel-preview.md](times-excel-preview.md)

## Налаштування

### Базовий експорт

Базова версія `ExportBulkAction` експортує всі видимі колонки таблиці автоматично:

```php
ExportBulkAction::make()
    ->label('Експорт у Excel')
```

### Розширені налаштування

Для більш детального налаштування експорту (назва файлу, вибір колонок, форматування) потрібно створити власний Exporter клас. Детальніше в офіційній документації пакету: https://github.com/pxlrbt/filament-excel

### Експорт усіх записів

Для експорту всіх записів (не тільки вибраних) можна додати окрему HeaderAction:

```php
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

->headerActions([
    ExportAction::make()
        ->label('Експортувати все'),
])
```

## Примітки

- Експортуються тільки вибрані записи
- Дані експортуються з урахуванням поточних фільтрів
- Формат файлу: `.xlsx` (Excel 2007+)
- Всі форматовані дані (наприклад, duration/3600) експортуються у вже обрахованому вигляді

