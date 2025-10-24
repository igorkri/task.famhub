# Сортировка записей в Filament

## Содержание
1. [Установка](#установка)
2. [Конфигурация](#конфигурация)
3. [Настройка модели](#настройка-модели)
4. [Настройка таблицы](#настройка-таблицы)
5. [Использование](#использование)
6. [Группировка и сортировка по уровням](#группировка-и-сортировка-по-уровням)

## Установка

Для работы сортировки необходимо установить пакет:

```bash
composer require spatie/eloquent-sortable
```

## Конфигурация

Файл конфигурации `config/sorting.php`:

```php
return [
    'default_column' => 'sort_order',
    'default_direction' => 'asc',
    'sort_when_creating' => true,
    'start_position' => 1,
    'group_sorting' => [
        'enabled' => true,
        'group_column' => 'parent_id',
    ],
];
```

## Настройка модели

1. Добавьте необходимые трейты и интерфейс:

```php
use App\Traits\HasSorting;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class YourModel extends Model implements Sortable
{
    use HasFactory, 
        SortableTrait {
            SortableTrait::buildSortQuery as parentBuildSortQuery;
        }
    use HasSorting {
        HasSorting::buildSortQuery insteadof SortableTrait;
    }

    protected $fillable = ['sort_order', 'parent_id'];
    
    public array $sortable;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->sortable = $this->getSortableConfig();
    }
}
```

2. Добавьте поле для сортировки в миграцию:

```php
Schema::create('your_table', function (Blueprint $table) {
    $table->id();
    $table->integer('sort_order')->default(0);
    $table->foreignId('parent_id')->nullable();
    // другие поля...
    $table->timestamps();
});
```

## Настройка таблицы в Filament

```php
public static function table(Table $table): Table
{
    return $table
        ->reorderable('sort_order')
        ->defaultSort('sort_order', 'asc')
        ->columns([
            TextColumn::make('sort_order')
                ->label('Порядок')
                ->sortable(),
            // другие колонки...
        ]);
}
```

## Использование

### Базовое использование

После настройки доступны следующие возможности:
1. Перетаскивание записей в таблице (drag-and-drop)
2. Автоматическая сортировка новых записей
3. Сохранение порядка внутри групп

### Программное управление

```php
// Получение отсортированного списка
$sorted = YourModel::ordered()->get();

// Перемещение записи
$model->moveToStart();
$model->moveToEnd();
$model->moveOrderUp();
$model->moveOrderDown();
```

## Группировка и сортировка по уровням

### Настройка группировки

1. В конфигурации:
```php
'group_sorting' => [
    'enabled' => true,
    'group_column' => 'parent_id',
],
```

2. В модели группировка уже реализована через HasSorting трейт.

### Особенности работы:
- Записи сортируются только внутри своей группы (например, категории одного уровня)
- При перетаскивании учитывается parent_id
- Новые записи добавляются в конец своей группы

### Рекомендации

1. Используйте индексы для оптимизации:
```php
$table->index(['parent_id', 'sort_order']);
```

2. При большом количестве записей включите пагинацию:
```php
->paginated([15, 30, 50, 100])
```

3. Для дерева категорий добавьте отображение уровня:
```php
TextColumn::make('parent.title')
    ->label('Родительская категория')
```

### Решение проблем

1. Конфликт методов buildSortQuery:
```php
use SortableTrait {
    SortableTrait::buildSortQuery as parentBuildSortQuery;
}
use HasSorting {
    HasSorting::buildSortQuery insteadof SortableTrait;
}
```

2. Сброс сортировки в группе:
```php
public function resetOrderInGroup($parentId = null)
{
    $query = $this->newQuery();
    if ($parentId !== null) {
        $query->where('parent_id', $parentId);
    }
    
    $query->ordered()->get()->each(function ($model, $index) {
        $model->update(['sort_order' => $index + 1]);
    });
}
```

### Важные замечания

1. Убедитесь, что все необходимые поля добавлены в $fillable
2. При использовании мягкого удаления учитывайте удаленные записи в сортировке
3. Следите за индексами в базе данных для оптимизации производительности
4. При изменении parent_id может потребоваться обновление sort_order
