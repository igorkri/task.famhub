# Работа с хлебными крошками в Filament

## Содержание
1. [Базовая структура](#базовая-структура)
2. [Настройка хлебных крошек](#настройка-хлебных-крошек)
3. [Примеры использования](#примеры-использования)

## Базовая структура

Хлебные крошки в Filament представляют собой массив, где:
- Ключ - это URL страницы
- Значение - это текст, который будет отображаться

```php
[
    '' => 'Контент',                      // Первый элемент без ссылки
    '/admin/categories' => 'Категории',    // Элемент со ссылкой
    '' => 'Текущая страница'              // Последний элемент без ссылки
]
```

## Настройка хлебных крошек

### Базовый метод getBreadcrumbs

```php
public function getBreadcrumbs(): array
{
    // Начальные хлебные крошки
    $breadcrumbs = [
        '' => __('Контент'),
    ];

    // Добавляем ссылку на список
    $breadcrumbs[$this->getResource()::getUrl('index')] = __('Категорії');

    return $breadcrumbs;
}
```

### Работа с иерархическими данными

```php
public function getBreadcrumbs(): array
{
    $breadcrumbs = [
        '' => __('Контент'),
        $this->getResource()::getUrl('index') => __('Категорії'),
    ];

    if (!$this->getRecord()) {
        return $breadcrumbs;
    }

    $locale = app()->getLocale();
    $record = $this->getRecord();

    // Собираем цепочку родителей
    $parents = collect();
    $currentParent = $record->parent;

    while ($currentParent) {
        $translation = $currentParent->translations()
            ->where('locale', $locale)
            ->first();

        if ($translation) {
            $parents->push([
                'id' => $currentParent->id,
                'title' => $translation->title,
            ]);
        }

        $currentParent = $currentParent->parent;
    }

    // Добавляем родителей в обратном порядке
    foreach ($parents->reverse() as $parent) {
        $breadcrumbs[$this->getResource()::getUrl('edit', ['record' => $parent['id']])] = $parent['title'];
    }

    // Добавляем текущую категорию
    $currentTranslation = $record->translations()
        ->where('locale', $locale)
        ->first();

    if ($currentTranslation) {
        $breadcrumbs[] = $currentTranslation->title;
    }

    return $breadcrumbs;
}
```

## Примеры использования

### 1. Простые хлебные крошки

```php
public function getBreadcrumbs(): array
{
    return [
        '' => __('Главная'),
        $this->getResource()::getUrl('index') => __('Список'),
        '' => __('Создать'),
    ];
}
```

### 2. Хлебные крошки с переводами

```php
$breadcrumbs = [
    '' => __('Контент'),
    $this->getResource()::getUrl('index') => __('Категорії'),
];
```

### 3. Динамические хлебные крошки

```php
// Добавляем элементы в зависимости от условий
if ($this->getRecord()->parent) {
    $breadcrumbs[$this->getResource()::getUrl('edit', ['record' => $this->getRecord()->parent->id])] = $this->getRecord()->parent->title;
}
```

### 4. Работа с переводами в хлебных крошках

```php
$translation = $record->translations()
    ->where('locale', app()->getLocale())
    ->first();

if ($translation) {
    $breadcrumbs[] = $translation->title;
}
```

## Важные замечания

1. Структура массива хлебных крошек:
   - Пустой ключ ('') для элементов без ссылки
   - URL в качестве ключа для кликабельных элементов
   - Значение массива - это отображаемый текст

2. Порядок элементов важен:
   - Первый элемент обычно "Контент" или название группы
   - Затем идет ссылка на список
   - Далее родительские элементы
   - Последний элемент - текущая страница

3. Работа с переводами:
   - Используйте хелпер __() для статических текстов
   - Получайте переводы из базы данных для динамического контента
   - Учитывайте текущую локаль (app()->getLocale())

4. Формирование URL:
   - Используйте $this->getResource()::getUrl('index') для списка
   - Используйте $this->getResource()::getUrl('edit', ['record' => $id]) для редактирования

5. Оптимизация:
   - Загружайте необходимые переводы через with()
   - Используйте кэширование для сложных иерархических структур
   - Группируйте запросы к базе данных
