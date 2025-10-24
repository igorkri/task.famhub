# Работа с переводами в Laravel Filament

## Содержание
1. [Структура базы данных](#структура-базы-данных)
2. [Миграции](#миграции)
3. [Модели](#модели)
4. [Трейт HasTranslations](#трейт-hastranslations)
5. [Filament Resource](#filament-resource)
6. [Работа с таблицами](#работа-с-таблицами)
7. [Примеры использования](#примеры-использования)

> **Note:** Документация по работе со slug доступна в отдельном файле [slug.md](slug.md)

## Структура базы данных

Для каждой сущности, требующей переводов, создаются две таблицы:
- Основная таблица (например, `article_categories`)
- Таблица переводов (например, `article_category_translations`)

### Пример структуры

```sql
-- Основная таблица
CREATE TABLE article_categories (
    id bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    slug varchar(255) NOT NULL,
    parent_id bigint UNSIGNED NULL,
    is_active boolean DEFAULT true,
    sort_order int DEFAULT 0,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    PRIMARY KEY (id)
);

-- Таблица переводов
CREATE TABLE article_category_translations (
    id bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id bigint UNSIGNED NOT NULL,
    locale varchar(5) NOT NULL,
    title varchar(255) NOT NULL,
    description text NULL,
    meta_title varchar(255) NULL,
    meta_description varchar(255) NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (category_id) REFERENCES article_categories(id) ON DELETE CASCADE
);
```

## Миграции

### 1. Создание миграций

```bash
php artisan make:migration create_article_categories_table
php artisan make:migration create_article_category_translations_table
```

### 2. Пример миграции для основной таблицы

```php
public function up()
{
    Schema::create('article_categories', function (Blueprint $table) {
        $table->id();
        $table->string('slug')->unique();
        $table->foreignId('parent_id')->nullable()->constrained('article_categories')->nullOnDelete();
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
}
```

### 3. Пример миграции для таблицы переводов

```php
public function up()
{
    Schema::create('article_category_translations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('category_id')->constrained('article_categories')->cascadeOnDelete();
        $table->string('locale', 5);
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('meta_title')->nullable();
        $table->string('meta_description')->nullable();
        $table->timestamps();

        $table->unique(['category_id', 'locale']);
    });
}
```

## Модели

### 1. Базовая модель Translation

```php
abstract class Translation extends Model
{
    public $timestamps = true;
    
    protected $fillable = [
        'locale',
        'title',
        'description',
        'meta_title',
        'meta_description',
    ];

    abstract public function translatable();
}
```

### 2. Модель перевода (например, ArticleCategoryTranslation)

```php
class ArticleCategoryTranslation extends Translation
{
    protected $fillable = [
        'category_id',
        'locale',
        'title',
        'description',
        'meta_title',
        'meta_description',
    ];

    public function translatable()
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }
}
```

### 3. Основная модель с переводами

```php
class ArticleCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['slug', 'parent_id', 'is_active', 'sort_order'];
    protected $with = ['translations']; // Автозагрузка переводов

    public function translations(): HasMany
    {
        return $this->hasMany(ArticleCategoryTranslation::class, 'category_id');
    }
}
```

## Трейт HasTranslations

Трейт `HasTranslations` предоставляет следующий функционал:

1. Автоматическое сохранение переводов
2. Получение переводов по локали
3. Получение текущего перевода

### Основные методы:

```php
// Установка переводов
$model->setTranslations([
    'uk' => ['title' => 'Назва'],
    'ru' => ['title' => 'Название'],
    'en' => ['title' => 'Title']
]);

// Получение перевода для конкретной локали
$model->getTranslation('uk', 'title');

// Получение перевода для текущей локали
$model->getCurrentTranslation('title');
```

## Filament Resource

### 1. Создание ресурса

```bash
php artisan make:filament-resource ArticleCategory
```

### 2. Настройка формы с переводами

```php
public static function form(Form $form): Form
{
    return $form->schema([
        // Основные поля
        TextInput::make('slug')->required(),
        
        // Вкладки с переводами
        Tabs::make('Translations')
            ->tabs(collect(['uk', 'ru', 'en'])->map(fn ($locale) => 
                Tab::make(strtoupper($locale))
                    ->schema([
                        TextInput::make("translations.{$locale}.title")
                            ->label("Название [$locale]")
                            ->required()
                            ->afterStateHydrated(function (TextInput $component, $state, $record) use ($locale) {
                                if ($record) {
                                    $translation = $record->translations->firstWhere('locale', $locale);
                                    $component->state($translation?->title);
                                }
                            }),
                        // Другие поля перевода
                    ])
            )->toArray())
    ]);
}
```

### 3. Настройка таблицы с переводами

```php
public static function table(Table $table): Table
{
    $locale = app()->getLocale();

    return $table
        ->modifyQueryUsing(fn (Builder $query) => $query
            ->with(['translations', 'parent.translations'])
        )
        ->columns([
            Tables\Columns\TextColumn::make('title')
                ->label('Название')
                ->formatStateUsing(fn ($record) => 
                    $record->translations->firstWhere('locale', $locale)?->title ?? ''
                )
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query
                        ->join('article_category_translations', 'article_categories.id', '=', 'article_category_translations.category_id')
                        ->where('article_category_translations.locale', app()->getLocale())
                        ->orderBy('article_category_translations.title', $direction)
                        ->select('article_categories.*');
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereHas('translations', function ($query) use ($search) {
                        $query->where('title', 'like', "%{$search}%");
                    });
                }),
            // Другие колонки
        ]);
}
```

## Работа с таблицами

При работе с переводами в таблицах Filament есть несколько важных моментов, которые нужно учитывать:

### 1. Базовая настройка таблицы

```php
public static function table(Table $table): Table
{
    $locale = app()->getLocale();

    return $table
        ->modifyQueryUsing(fn (Builder $query) => $query
            ->with(['translations', 'parent.translations'])
        )
        ->columns([
            // колонки
        ]);
}
```

### 2. Настройка колонок с переводами

```php
Tables\Columns\TextColumn::make('title')
    ->label('Название')
    ->formatStateUsing(fn ($record) => 
        $record->translations->firstWhere('locale', app()->getLocale())?->title ?? ''
    )
    ->sortable(query: function (Builder $query, string $direction): Builder {
        return $query
            ->join('article_category_translations', 'article_categories.id', '=', 'article_category_translations.category_id')
            ->where('article_category_translations.locale', app()->getLocale())
            ->orderBy('article_category_translations.title', $direction)
            ->select('article_categories.*');
    })
    ->searchable(query: function (Builder $query, string $search): Builder {
        return $query->whereHas('translations', function ($query) use ($search) {
            $query->where('title', 'like', "%{$search}%");
        });
    })
```

### 3. Оптимизация производительности

1. Предварительная загрузка отношений:
```php
->modifyQueryUsing(fn (Builder $query) => $query->with(['translations']))
```

2. Правильное использование индексов:
```php
// В миграции
$table->index(['locale']);
$table->index(['category_id', 'locale']);
```

3. Кэширование запросов при необходимости:
```php
->modifyQueryUsing(fn (Builder $query) => $query
    ->with(['translations'])
    ->remember(now()->addHour())
)
```

### 4. Особенности работы с переводами в таблице

1. **Отображение значений:**
- Используйте formatStateUsing для форматирования вывода
- Учитывайте возможность отсутствия перевода
- Используйте текущую локаль приложения

2. **Сортировка:**
- Всегда используйте JOIN для сортировки по переведенным полям
- Указывайте конкретную локаль при сортировке
- Выбирайте только нужные поля через select

3. **Поиск:**
- Используйте whereHas для поиска по связанным таблицам
- Учитывайте регистр при поиске
- Оптимизируйте запросы для больших объемов данных

### 5. Примеры решения типичных проблем

1. **Сортировка по нескольким переводам:**
```php
->sortable(query: function (Builder $query, string $direction): Builder {
    return $query
        ->leftJoin('article_category_translations as t1', function ($join) {
            $join->on('article_categories.id', '=', 't1.category_id')
                 ->where('t1.locale', app()->getLocale());
        })
        ->orderBy('t1.title', $direction)
        ->select('article_categories.*');
})
```

2. **Поиск по всем локалям:**
```php
->searchable(query: function (Builder $query, string $search): Builder {
    return $query->whereHas('translations', function ($query) use ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    });
})
```

3. **Фильтрация по переводам:**
```php
->filters([
    Tables\Filters\SelectFilter::make('locale')
        ->options([
            'uk' => 'Украинский',
            'ru' => 'Русский',
            'en' => 'Английский'
        ])
        ->query(function (Builder $query, string $locale) {
            return $query->whereHas('translations', fn ($q) => 
                $q->where('locale', $locale)
            );
        })
])
```

### 6. Рекомендации по производительности

1. Избегайте N+1 запросов:
```php
protected $with = ['translations'];
```

2. Используйте составные индексы:
```php
$table->index(['category_id', 'locale', 'title']);
```

3. Оптимизируйте запросы:
```php
->modifyQueryUsing(fn (Builder $query) => $query
    ->select('article_categories.*')
    ->with(['translations' => fn($q) => $q->select(['category_id', 'locale', 'title'])])
)
```

## Примеры использования

### 1. Создание новой сущности с переводами

```php
$category = ArticleCategory::create([
    'slug' => 'example',
    'is_active' => true
]);

$category->setTranslations([
    'uk' => [
        'title' => 'Приклад',
        'description' => 'Опис'
    ],
    'ru' => [
        'title' => 'Пример',
        'description' => 'Описание'
    ],
    'en' => [
        'title' => 'Example',
        'description' => 'Description'
    ]
]);

$category->save();
```

### 2. Получение переводов

```php
// Получить перевод для конкретного языка
$title = $category->getTranslation('uk', 'title');

// Получить перевод для текущего языка
$description = $category->getCurrentTranslation('description');

// Получить все переводы
$translations = $category->translations;
```

### 3. Обновление переводов

```php
$category->setTranslations([
    'uk' => ['title' => 'Нова назва'],
    'ru' => ['title' => 'Новое название']
]);
$category->save();
```

### 4. Валидация в Filament

```php
class ArticleCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255'],
            'translations' => ['required', 'array'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'translations.*.title.required' => 'Название обязательно для всех языков',
        ];
    }
}
```
