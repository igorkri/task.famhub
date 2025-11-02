# Уведомления о задачах в навигации (Badge)

## Описание
В ресурсе `TaskResource` реализована функциональность отображения badge (уведомлений) в боковой навигации Filament. Badge показывает количество активных задач.

## Реализация

### Методы в TaskResource

#### `getNavigationBadge()`
Возвращает количество активных задач для отображения в badge.

**Критерии подсчёта:**
- Задачи со статусом `new` (Новий)
- Задачи со статусом `in_progress` (В процесі)
- Только не завершённые задачи (`is_completed = false`)

**Возвращаемое значение:**
- Строка с числом, если есть активные задачи
- `null`, если активных задач нет (badge не отображается)

#### `getNavigationBadgeColor()`
Определяет цвет badge в зависимости от количества активных задач.

**Цветовая индикация:**
- **null** (не отображается): 0 задач
- **success** (зелёный): 1-4 задачи
- **warning** (жёлтый): 5-9 задач
- **danger** (красный): 10+ задач

## Примеры использования

### В боковой навигации Filament
Badge автоматически отображается рядом с пунктом меню "Задачі":

```
📋 Задачі [3]  <- зелёный badge (1-4 задачи)
📋 Задачі [7]  <- жёлтый badge (5-9 задач)
📋 Задачі [15] <- красный badge (10+ задач)
```

## Код реализации

```php
public static function getNavigationBadge(): ?string
{
    $count = Task::whereIn('status', [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS])
        ->where('is_completed', false)
        ->count();

    return $count > 0 ? (string) $count : null;
}

public static function getNavigationBadgeColor(): string|array|null
{
    $count = Task::whereIn('status', [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS])
        ->where('is_completed', false)
        ->count();

    return match (true) {
        $count === 0 => null,
        $count < 5 => 'success',
        $count < 10 => 'warning',
        default => 'danger',
    };
}
```

## Тестирование

Создан комплексный набор тестов в `tests/Feature/TaskNavigationBadgeTest.php`:

- `test_navigation_badge_shows_count_of_active_tasks` - проверка подсчёта активных задач
- `test_navigation_badge_returns_null_when_no_active_tasks` - проверка отсутствия badge при 0 задачах
- `test_navigation_badge_color_is_success_for_few_tasks` - проверка зелёного цвета (1-4)
- `test_navigation_badge_color_is_warning_for_moderate_tasks` - проверка жёлтого цвета (5-9)
- `test_navigation_badge_color_is_danger_for_many_tasks` - проверка красного цвета (10+)
- `test_navigation_badge_excludes_completed_tasks` - проверка исключения завершённых задач

### Запуск тестов

```bash
php artisan test --filter=TaskNavigationBadgeTest
```

## Настройка

Если вам нужно изменить критерии отображения или цветовую схему:

### Изменить подсчитываемые статусы
Измените массив статусов в `whereIn()`:
```php
Task::whereIn('status', [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS, Task::STATUS_NEEDS_CLARIFICATION])
```

### Изменить пороги цветовой индикации
Измените условия в методе `getNavigationBadgeColor()`:
```php
return match (true) {
    $count === 0 => null,
    $count < 3 => 'success',    // 1-2 задачи
    $count < 7 => 'warning',    // 3-6 задач
    default => 'danger',        // 7+ задач
};
```

### Добавить фильтр по пользователю
Чтобы показывать только задачи текущего пользователя:
```php
Task::whereIn('status', [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS])
    ->where('is_completed', false)
    ->where('user_id', auth()->id())
    ->count();
```

## Производительность

Badge подсчитывается при каждой загрузке страницы с навигацией. Для оптимизации можно:

1. **Добавить кеширование:**
```php
public static function getNavigationBadge(): ?string
{
    $count = Cache::remember('tasks.active.count', 60, function () {
        return Task::whereIn('status', [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS])
            ->where('is_completed', false)
            ->count();
    });

    return $count > 0 ? (string) $count : null;
}
```

2. **Сбрасывать кеш при изменении задач** (в Observer):
```php
Cache::forget('tasks.active.count');
```

## Связанные файлы

- `app/Filament/Resources/Tasks/TaskResource.php` - основная реализация
- `app/Models/Task.php` - модель задачи
- `tests/Feature/TaskNavigationBadgeTest.php` - тесты
- `test-badge.php` - скрипт для ручной проверки

