# Історія змін тасків

## Огляд

Система історії змін автоматично логує всі зміни тасків з різних джерел:
- **Локальні зміни** - через Filament UI або безпосередньо в БД
- **Webhooks з Asana** - автоматичні зміни з Asana
- **Синхронізація з Asana** - ручна синхронізація

## Структура таблиці `task_histories`

| Поле | Тип | Опис |
|------|-----|------|
| `task_id` | FK | ID таска |
| `user_id` | FK nullable | Користувач, який зробив зміну |
| `event_type` | string | Тип події (14 варіантів) |
| `source` | string | Джерело: local, asana_webhook, asana_sync |
| `field_name` | string nullable | Назва поля, що змінилось |
| `old_value` | text nullable | Старе значення |
| `new_value` | text nullable | Нове значення |
| `changes` | json nullable | Масив змін для batch-оновлень |
| `metadata` | json nullable | Додаткові дані (для Asana подій) |
| `description` | text nullable | Опис події українською |
| `event_at` | datetime | Час події |

## Типи подій (`event_type`)

1. **created** - Таск створено
2. **updated** - Загальне оновлення
3. **deleted** - Таск видалено
4. **status_changed** - Зміна статусу
5. **assigned** - Призначено виконавця
6. **unassigned** - Знято виконавця
7. **section_changed** - Зміна секції
8. **priority_changed** - Зміна пріоритету
9. **deadline_changed** - Зміна дедлайну
10. **completed** - Таск завершено
11. **reopened** - Таск відновлено
12. **comment_added** - Додано коментар
13. **attachment_added** - Додано файл
14. **custom_field_changed** - Зміна кастомного поля

## Джерела змін (`source`)

- **local** - Зміни через локальний інтерфейс або API
- **asana_webhook** - Автоматичні вебхуки з Asana
- **asana_sync** - Ручна синхронізація з Asana

## Використання в коді

### Отримання історії таска

```php
// Вся історія
$task = Task::find(1);
$history = $task->histories; // колекція TaskHistory

// З сортуванням
$history = $task->histories()->latest('event_at')->get();

// Фільтрація по типу події
$statusChanges = TaskHistory::where('task_id', 1)
    ->where('event_type', TaskHistory::EVENT_STATUS_CHANGED)
    ->get();

// Фільтрація по джерелу
$asanaChanges = TaskHistory::where('task_id', 1)
    ->where('source', TaskHistory::SOURCE_ASANA_WEBHOOK)
    ->get();

// Тільки зміни конкретного користувача
$userChanges = TaskHistory::where('task_id', 1)
    ->where('user_id', auth()->id())
    ->get();
```

### Ручне створення запису в історії

```php
use App\Models\TaskHistory;

// Логування зміни одного поля
TaskHistory::logFieldChange(
    taskId: 1,
    fieldName: 'status',
    oldValue: 'new',
    newValue: 'in_progress',
    source: TaskHistory::SOURCE_LOCAL,
    userId: auth()->id()
);

// Логування batch-змін
TaskHistory::logBatchChanges(
    taskId: 1,
    changes: [
        'status' => ['old' => 'new', 'new' => 'in_progress'],
        'priority' => ['old' => 'low', 'new' => 'high'],
    ],
    source: TaskHistory::SOURCE_LOCAL,
    userId: auth()->id(),
    metadata: ['additional' => 'data']
);

// Логування події без зміни полів
TaskHistory::logEvent(
    taskId: 1,
    eventType: TaskHistory::EVENT_COMMENT_ADDED,
    source: TaskHistory::SOURCE_LOCAL,
    userId: auth()->id(),
    description: 'Додано коментар від користувача',
    metadata: ['comment_id' => 123]
);
```

## Перегляд в Filament UI

У Filament Resource для тасків автоматично додано таб **"Історія змін"** з:

### Колонки таблиці:
- **Дата і час** - коли сталась зміна
- **Джерело** - іконка джерела (комп'ютер/глобус/sync)
- **Подія** - тип події з кольоровим бейджем
- **Поле** - яке поле змінилось
- **Було/Стало** - старе та нове значення
- **Користувач** - хто зробив зміну

### Фільтри:
- По типу події (множинний вибір)
- По джерелу змін

### Особливості:
- ✅ Тільки для читання (без можливості редагування)
- ✅ Автоматичне сортування за датою (нові зверху)
- ✅ Пагінація (10/25/50/100 записів)
- ✅ Іконки та кольори для кращої візуалізації
- ✅ Підказки (tooltips) для довгих значень

## Автоматичне логування

### TaskObserver
Автоматично логує всі локальні зміни через Observer:
- При створенні таска → `EVENT_CREATED`
- При оновленні полів → відповідний тип події
- При видаленні → `EVENT_DELETED`
- При відновленні → `EVENT_CREATED` з описом "відновлено"

### ProcessAsanaWebhookJob
Автоматично логує зміни з вебхуків Asana:
- Використовує `Task::withoutEvents()` щоб уникнути подвійного логування
- Зберігає повні дані події в `metadata`
- Логує batch-зміни одним записом

## Приклади використання

### Аудит змін статусу
```php
// Знайти всі зміни статусу за останній місяць
$statusChanges = TaskHistory::where('event_type', TaskHistory::EVENT_STATUS_CHANGED)
    ->where('event_at', '>=', now()->subMonth())
    ->with(['task', 'user'])
    ->get();

foreach ($statusChanges as $change) {
    echo "{$change->task->title}: {$change->old_value} → {$change->new_value}\n";
    echo "Користувач: {$change->user->name}, Час: {$change->event_at}\n";
}
```

### Звіт по змінам з Asana
```php
// Всі зміни отримані через вебхуки за сьогодні
$asanaChanges = TaskHistory::where('source', TaskHistory::SOURCE_ASANA_WEBHOOK)
    ->whereDate('event_at', today())
    ->with('task')
    ->get();
```

### Активність користувача
```php
// Всі дії користувача за тиждень
$userActivity = TaskHistory::where('user_id', $userId)
    ->where('event_at', '>=', now()->subWeek())
    ->orderBy('event_at', 'desc')
    ->get();
```

## Налаштування

### Відключення автоматичного логування для конкретної операції
```php
use App\Models\Task;

// Тимчасово відключити Observer
Task::withoutEvents(function () use ($task) {
    $task->update(['status' => 'completed']);
    // Ця зміна НЕ буде залогована
});
```

### Форматування значень
Модель `TaskHistory` автоматично форматує значення українською:
- Статуси → "Новий", "В роботі" і т.д.
- Пріоритети → "Високий", "Середній", "Низький"
- Дати → "25.10.2025"
- Boolean → "Так" / "Ні"

## Індекси для продуктивності

Для швидкого пошуку створено індекси:
- `(task_id, event_at)` - для сортування історії таска
- `(task_id, event_type)` - для фільтрації по типу події

## Обмеження

- ⚠️ Історія тільки для читання через UI
- ⚠️ Видалення таска каскадно видаляє всю його історію
- ⚠️ Старі значення зберігаються як текст (не як FK)
