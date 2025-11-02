# Badge в навігації задач ✅

## Швидкий старт

У навігації Filament тепер автоматично відображається кількість активних задач:

- ✅ **Зелений [1-4]** - мало задач
- ⚠️ **Жовтий [5-9]** - помірна кількість
- 🔴 **Червоний [10+]** - багато задач

## Що рахується

Тільки ��адачі зі статусами:
- 🆕 Новий
- ⏳ В процесі

І тільки не завершені (`is_completed = false`)

## Файли

- ✏️ **Основний код**: `app/Filament/Resources/Tasks/TaskResource.php`
- 🧪 **Тести**: `tests/Feature/TaskNavigationBadgeTest.php`
- 📖 **Документація**: `docs/task-navigation-badge-uk.md`

## Команди

```bash
# Запустити тести
php artisan test --filter=TaskNavigationBadgeTest

# Перевірити роботу
php test-badge.php

# Форматування коду
vendor/bin/pint app/Filament/Resources/Tasks/TaskResource.php
```

## Приклад коду

```php
// Додано в TaskResource.php

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

Готово! 🎉

