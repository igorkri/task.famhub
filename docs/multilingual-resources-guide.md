# Руководство по созданию многоязычных ресурсов в Filament

Это подробное руководство покажет, как создать многоязычный ресурс на примере `ArticleCategory` с поддержкой украинского, английского и русского языков.

## Оглавление

1. [Подготовка проекта](#1-подготовка-проекта)
2. [Подготовка базы данных](#2-подготовка-базы-данных)
3. [Создание моделей](#3-создание-моделей)
4. [Создание трейтов](#4-создание-трейтов)
5. [Создание Filament ресурса](#5-создание-filament-ресурса)
6. [Настройка валидации](#6-настройка-валидации)
7. [Тестирование](#7-тестирование)

## 1. Подготовка проекта

### 1.1 Установка необходимых пакетов

```bash
# Установка пакета для сортировки (если еще не установлен)
composer require spatie/eloquent-sortable

# Очистка кэша конфигурации
php artisan config:clear
```

### 1.2 Проверка существующих файлов

Убедитесь, что у вас есть следующие файлы в проекте:
- `config/translations.php` - конфигурация переводов
- `config/sorting.php` - конфигурация сортировки

## 2. Подготовка базы данных

### 2.1 Создание миграции основной таблицы

```bash
php artisan make:migration create_article_categories_table
```

**Файл:** `database/migrations/xxxx_xx_xx_xxxxxx_create_article_categories_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('article_categories')->onDelete('cascade');
            $table->index(['parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_categories');
    }
};
```

### 2.2 Создание миграции таблицы переводов

```bash
php artisan make:migration create_article_category_translations_table
```

**Файл:** `database/migrations/xxxx_xx_xx_xxxxxx_create_article_category_translations_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_category_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('locale', 5);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('article_categories')->onDelete('cascade');
            $table->unique(['category_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_category_translations');
    }
};
```

### 2.3 Запуск миграций

```bash
php artisan migrate
```

## 3. Создание моделей

⚠️ **Важно**: Все модели уже созданы в проекте и находятся в папке `app/Models/`. Этот раздел приводится для справки и содержит команды для создания аналогичных моделей.

### Консольные команды для создания моделей:

```bash
# Создание базовой модели Translation (абстрактная модель)
php artisan make:model Translation

# Создание модели категорий статей
php artisan make:model ArticleCategory

# Создание модели переводов категорий
php artisan make:model ArticleCategoryTranslation

# Создание модели статей
php artisan make:model Article

# Создание модели переводов статей  
php artisan make:model ArticleTranslation

# Создание модели с миграцией, фабрикой и сидером (полный набор)
php artisan make:model ArticleCategory -mfs

# Создание только модели с миграцией
php artisan make:model ArticleCategory -m
```

### 3.1 Базовая модель Translation ✅ (уже существует)

**Файл:** `app/Models/Translation.php` 

Эта модель уже существует и содержит необходимые методы для работы с языками:
- `getLanguages()` - получение списка поддерживаемых языков
- `getLanguageKeys()` - получение ключей языков

### 3.2 Модель ArticleCategoryTranslation ✅ (уже существует)

**Файл:** `app/Models/ArticleCategoryTranslation.php`

Эта модель уже существует и правильно настроена с:
- Связью с основной моделью через `translatable()`
- Правильными полями в `$fillable`

### 3.3 Основная модель ArticleCategory ✅ (уже существует)

**Файл:** `app/Models/ArticleCategory.php`

Модель уже существует с правильными трейтами и отношениями:
- `HasTranslations` - для работы с переводами
- `HasSlug` - для работы со slug
- `SortableTrait` - для сортировки записей
- Связи `translations()`, `parent()`, `children()`

## 4. Создание трейтов

### 4.1 Трейт HasTranslations (уже существует)

**Файл:** `app/Traits/HasTranslations.php` (уже создан в проекте)

Трейт уже создан и содержит все необходимые методы для работы с переводами.

### 4.2 Трейт HasSlug (уже существует)

**Файл:** `app/Traits/HasSlug.php` (уже создан в проекте)

Трейт существует и интегрирован с системой переводов.

## 5. Создание Filament ресурса

### 5.1 Создание ресурса

```bash
php artisan make:filament-resource ArticleCategory --generate --simple
```

### 5.2 Создание кастомных страниц ресурса

```bash
php artisan make:filament-page ManageArticleCategories --resource=ArticleCategoryResource --type=ManageRecords
```

### 5.3 Настройка формы ресурса

**Файл:** `app/Filament/Admin/Resources/ArticleCategoryResource.php`

```php
<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ArticleCategoryResource\Pages;
use App\Models\ArticleCategory;
use App\Models\Translation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;

class ArticleCategoryResource extends Resource
{
    protected static ?string $model = ArticleCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Категорія';
    protected static ?string $pluralModelLabel = 'Категорії';
    protected static ?string $navigationLabel = 'Категорії статей';
    protected static ?string $breadcrumb = 'Категорії';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Контент';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('URL-ідентифікатор. Можна згенерувати з поля "Назва (UK)"')
                            ->columnSpanFull()
                            ->suffixAction(
                                Action::make('generateSlug')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(function (Get $get, Set $set) {
                                        $title = $get('translations.uk.title');
                                        if ($title) {
                                            $set('slug', Str::slug($title));
                                        }
                                    })
                                    ->tooltip('Згенерувати з назви')
                            ),

                        Select::make('parent_id')
                            ->label('Батьківська категорія')
                            ->relationship('parent', 'slug')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(1),
                    ])
                    ->columns(1),

                Grid::make()
                    ->schema([
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->label('Порядок сортування')
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                // Секция переводов
                Tabs::make('Translations')
                    ->tabs(collect(Translation::getLanguageKeys())->map(fn ($locale) =>
                        Tab::make(Translation::getLanguages($locale) . ' [' . strtoupper($locale) . ']')
                            ->icon('heroicon-o-language')
                            ->schema([
                                TextInput::make("translations.{$locale}.title")
                                    ->label("Назва [$locale]")
                                    ->required()
                                    ->validationAttribute("назва ($locale)")
                                    ->helperText('Обов\'язкове поле')
                                    ->afterStateHydrated(function (TextInput $component, $state, $record) use ($locale) {
                                        if ($record) {
                                            $translation = $record->translations->firstWhere('locale', $locale);
                                            $component->state($translation?->title);
                                        }
                                    })
                                    ->columnSpanFull(),

                                RichEditor::make("translations.{$locale}.description")
                                    ->label("Опис [$locale]")
                                    ->validationAttribute("опис ($locale)")
                                    ->columnSpanFull()
                                    ->afterStateHydrated(function ($component, $state, $record) use ($locale) {
                                        if ($record) {
                                            $translation = $record->translations->firstWhere('locale', $locale);
                                            $component->state($translation?->description);
                                        }
                                    }),

                                TextInput::make("translations.{$locale}.meta_title")
                                    ->label("Meta Title [$locale]")
                                    ->validationAttribute("meta заголовок ($locale)")
                                    ->columnSpanFull()
                                    ->afterStateHydrated(function (TextInput $component, $state, $record) use ($locale) {
                                        if ($record) {
                                            $translation = $record->translations->firstWhere('locale', $locale);
                                            $component->state($translation?->meta_title);
                                        }
                                    }),

                                Textarea::make("translations.{$locale}.meta_description")
                                    ->label("Meta Description [$locale]")
                                    ->validationAttribute("meta опис ($locale)")
                                    ->columnSpanFull()
                                    ->afterStateHydrated(function (Textarea $component, $state, $record) use ($locale) {
                                        if ($record) {
                                            $translation = $record->translations->firstWhere('locale', $locale);
                                            $component->state($translation?->meta_description);
                                        }
                                    }),
                            ])
                    )->toArray())
                    ->columnSpanFull()
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('translation.title')
                    ->label('Назва')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('parent.translation.title')
                    ->label('Батьківська категорія')
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options([
                        true => 'Активна',
                        false => 'Неактивна',
                    ]),
                    
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Батьківська категорія')
                    ->relationship('parent', 'slug')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageArticleCategories::route('/'),
        ];
    }
}
```

### 5.4 Создание страницы управления

**Файл:** `app/Filament/Admin/Resources/ArticleCategoryResource/Pages/ManageArticleCategories.php`

```php
<?php

namespace App\Filament\Admin\Resources\ArticleCategoryResource\Pages;

use App\Filament\Admin\Resources\ArticleCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageArticleCategories extends ManageRecords
{
    protected static string $resource = ArticleCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $translations = $data['translations'] ?? [];
                    unset($data['translations']);
                    return $data;
                })
                ->after(function ($record, array $data) {
                    $translations = $data['translations'] ?? [];
                    if (!empty($translations)) {
                        foreach ($translations as $locale => $translation) {
                            if (!empty(array_filter($translation))) {
                                $record->translations()->create([
                                    'locale' => $locale,
                                    ...$translation
                                ]);
                            }
                        }
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Заполняем данные переводов для формы при редактировании
        if (isset($this->record)) {
            $translations = [];
            foreach ($this->record->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'description' => $translation->description,
                    'meta_title' => $translation->meta_title,
                    'meta_description' => $translation->meta_description,
                ];
            }
            $data['translations'] = $translations;
        }
        
        return $data;
    }
}
```

## 6. Настройка валидации

### 6.1 Создание Request класса

```bash
php artisan make:request ArticleCategoryRequest
```

**Файл:** `app/Http/Requests/ArticleCategoryRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Translation;

class ArticleCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'slug' => 'required|string|max:255|unique:article_categories,slug,' . $this->route('record'),
            'parent_id' => 'nullable|exists:article_categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];

        // Добавляем правила для переводов
        foreach (Translation::getLanguageKeys() as $locale) {
            $rules["translations.{$locale}.title"] = 'required|string|max:255';
            $rules["translations.{$locale}.description"] = 'nullable|string';
            $rules["translations.{$locale}.meta_title"] = 'nullable|string|max:255';
            $rules["translations.{$locale}.meta_description"] = 'nullable|string|max:500';
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [];

        foreach (Translation::getLanguageKeys() as $locale) {
            $langName = Translation::getLanguages($locale);
            $messages["translations.{$locale}.title.required"] = "Поле 'Назва' для мови {$langName} обов'язкове для заповнення.";
            $messages["translations.{$locale}.title.max"] = "Поле 'Назва' для мови {$langName} не повинно перевищувати 255 символів.";
        }

        return $messages;
    }
}
```

## 7. Тестирование

### 7.1 Создание сідера для тестовых данных

```bash
php artisan make:seeder ArticleCategorySeeder
```

**Файл:** `database/seeders/ArticleCategorySeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ArticleCategory;

class ArticleCategorySeeder extends Seeder
{
    public function run(): void
    {
        $category = ArticleCategory::create([
            'slug' => 'test-category',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $category->translations()->createMany([
            [
                'locale' => 'uk',
                'title' => 'Тестова категорія',
                'description' => 'Опис тестової категорії українською мовою',
                'meta_title' => 'Мета заголовок українською',
                'meta_description' => 'Мета опис українською мовою',
            ],
            [
                'locale' => 'en',
                'title' => 'Test Category',
                'description' => 'Test category description in English',
                'meta_title' => 'Meta title in English',
                'meta_description' => 'Meta description in English',
            ],
            [
                'locale' => 'ru',
                'title' => 'Тестовая категория',
                'description' => 'Описание тестовой категории на русском языке',
                'meta_title' => 'Мета заголовок на русском',
                'meta_description' => 'Мета описание на русском языке',
            ],
        ]);

        // Создаем дочернюю категорию
        $childCategory = ArticleCategory::create([
            'slug' => 'child-category',
            'parent_id' => $category->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $childCategory->translations()->createMany([
            [
                'locale' => 'uk',
                'title' => 'Дочірня категорія',
                'description' => 'Опис дочірньої категорії',
            ],
            [
                'locale' => 'en',
                'title' => 'Child Category',
                'description' => 'Child category description',
            ],
            [
                'locale' => 'ru',
                'title' => 'Дочерняя категория',
                'description' => 'Описание дочерней категории',
            ],
        ]);
    }
}
```

### 7.2 Обновление DatabaseSeeder

```bash
# Отредактируйте файл database/seeders/DatabaseSeeder.php
```

**Файл:** `database/seeders/DatabaseSeeder.php`

```php
// ...existing code...
public function run(): void
{
    // ...existing code...
    $this->call([
        ArticleCategorySeeder::class,
    ]);
}
```

### 7.3 Запуск сідера

```bash
php artisan db:seed --class=ArticleCategorySeeder
```

### 7.4 Создание тинкер-команд для тестирования

```bash
# Запуск интерактивной консоли
php artisan tinker
```

В tinker выполните:

```php
// Проверка структуры таблиц
DB::select('DESCRIBE article_categories');
DB::select('DESCRIBE article_category_translations');

// Проверка данных
App\Models\ArticleCategory::with('translations')->get();
```

## 8. Проверка функциональности

### 8.1 Проверка в Filament Admin

1. Откройте браузер и перейдите на `/admin`
2. Войдите в админ-панель
3. Перейдите в раздел "Контент" → "Категорії статей"
4. Создайте новую категорию с переводами
5. Проверьте редактирование существующих категорий
6. Протестируйте сортировку записей

### 8.2 Проверка в базе данных

```bash
# Подключение к базе данных
php artisan tinker
```

```php
// Проверка структуры таблиц
DB::select('DESCRIBE article_categories');
DB::select('DESCRIBE article_category_translations');

// Проверка данных
App\Models\ArticleCategory::with('translations')->get();
```

## 9. Использование в коде

### 9.1 Получение переводов

```php
// В контроллере или модели
$category = ArticleCategory::find(1);

// Получить текущий перевод
$currentTranslation = $category->getCurrentTranslation();
$title = $category->getCurrentTranslation('title');

// Получить конкретный перевод
$ukTranslation = $category->getTranslation('uk');
$ukTitle = $category->getTranslation('uk', 'title');

// Использование связи
$category = ArticleCategory::with('translation')->find(1);
$title = $category->translation->title;
```

### 9.2 Создание категории программно

```php
$category = new ArticleCategory([
    'slug' => 'new-category',
    'is_active' => true,
]);

$category->setTranslations([
    'uk' => [
        'title' => 'Нова категорія',
        'description' => 'Опис нової категорії',
    ],
    'en' => [
        'title' => 'New Category',
        'description' => 'New category description',
    ],
    'ru' => [
        'title' => 'Новая категория',
        'description' => 'Описание новой категории',
    ],
]);

$category->save();
```

## Заключение

Эта система многоязычности обеспечивает:

- ✅ Простое управление переводами через Filament интерфейс
- ✅ Валидацию для всех языков  
- ✅ Автоматическое создание slug из названия
- ✅ Гибкую систему получения переводов
- ✅ Поддержку вложенных категорий
- ✅ Сортировку записей
- ✅ Интеграцию с существующими трейтами проекта

Данный подход можно использовать для любых других многоязычных ресурсов в проекте, адаптируя поля и структуру под конкретные потребности.
