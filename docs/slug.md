# Автогенерация Slug в Laravel Filament

## Содержание
1. [Конфигурация](#конфигурация)
2. [Настройка трейта](#настройка-трейта)
3. [Использование в моделях](#использование-в-моделях)
4. [Настройка в Filament](#настройка-в-filament)
5. [Примеры использования](#примеры-использования)

## Конфигурация

Настройки для автогенерации slug находятся в файле `config/translations.php`:

```php
return [
    'slug' => [
        'source_field' => 'title', // поле, из которого генерируется slug
        'source_locale' => 'uk',   // локаль, из которой генерируется slug
    ],
];
```

## Настройка трейта

Трейт `HasSlug` предоставляет функционал автоматической генерации slug из указанного поля перевода:

```php
trait HasSlug
{
    protected function initializeHasSlug(): void
    {
        $this->fillable[] = 'slug';
    }

    protected function getSlugSourceField(): string
    {
        return config('translations.slug.source_field', 'title');
    }

    protected function getSlugSourceLocale(): string
    {
        return config('translations.slug.source_locale', 'uk');
    }

    public function generateSlug(): string
    {
        $sourceField = $this->getSlugSourceField();
        $sourceLocale = $this->getSlugSourceLocale();
        
        $translation = $this->translations
            ->where('locale', $sourceLocale)
            ->first();

        if (!$translation) {
            return '';
        }

        return Str::slug($translation->{$sourceField});
    }

    public function updateSlug(): void
    {
        $this->slug = $this->generateSlug();
        $this->save();
    }
}
```

## Использование в моделях

```php
use App\Traits\HasSlug;

class YourModel extends Model
{
    use HasFactory, HasTranslations, HasSlug;

    protected $fillable = ['slug', 'other_fields'];
}
```

### Кастомизация источника для slug

```php
class YourModel extends Model
{
    use HasFactory, HasTranslations, HasSlug;

    protected function getSlugSourceField(): string
    {
        return 'name'; // использовать поле name вместо title
    }

    protected function getSlugSourceLocale(): string
    {
        return 'en'; // использовать английский перевод
    }
}
```

## Настройка в Filament

```php
public static function form(Form $form): Form
{
    $sourceLocale = config('translations.slug.source_locale', 'uk');
    $sourceField = config('translations.slug.source_field', 'title');

    return $form->schema([
        TextInput::make('slug')
            ->required()
            ->unique(ignoreRecord: true)
            ->disabled()
            ->helperText(sprintf('Генерируется из поля "%s" (%s)', $sourceField, strtoupper($sourceLocale))),

        // Поле, из которого генерируется slug
        TextInput::make("translations.{$sourceLocale}.{$sourceField}")
            ->reactive()
            ->afterStateUpdated(function ($state, $set) {
                $set('slug', Str::slug($state));
            }),
    ]);
}
```

## Примеры использования

### 1. Базовое использование
```php
// Модель автоматически генерирует slug при создании/обновлении
$model = YourModel::create([
    'translations' => [
        'uk' => ['title' => 'Назва'],
        'ru' => ['title' => 'Название'],
        'en' => ['title' => 'Title']
    ]
]);

// Slug будет сгенерирован из украинского заголовка
echo $model->slug; // "nazva"
```

### 2. Ручная генерация slug
```php
$model = YourModel::find(1);
$model->updateSlug(); // перегенерировать slug
```

### 3. Изменение источника для slug в конфигурации
```php
// config/translations.php
'slug' => [
    'source_field' => 'name',
    'source_locale' => 'en',
],
```

## Важные замечания

1. Slug генерируется автоматически при создании/обновлении записи
2. Используется перевод из указанной локали по умолчанию
3. Slug уникален в пределах таблицы
4. Поле slug отключено для ручного редактирования в форме
5. При отсутствии перевода в указанной локали slug будет пустым

## Рекомендации

1. Добавляйте уникальный индекс для поля slug в миграции:
```php
$table->string('slug')->unique();
```

2. Добавляйте валидацию в FormRequest:
```php
'slug' => ['required', 'string', 'max:255', 'unique:articles,slug,' . $this->id],
```

3. При необходимости можно изменить логику генерации slug:
```php
protected function generateSlug(): string
{
    // Ваша логика генерации slug
    return Str::slug($this->getTranslation($this->getSlugSourceLocale())->{$this->getSlugSourceField()});
}
