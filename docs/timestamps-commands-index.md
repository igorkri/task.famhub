# 🕐 Команди оновлення Timestamps - Головна сторінка

## Огляд

Цей проект містить дві консольні команди для оновлення часових міток (`created_at` та `updated_at`) з різних джерел API.

---

## 🎯 Швидкий вибір команди

### Що ви хочете оновити?

| Потрібно оновити | Команда | Документація |
|------------------|---------|--------------|
| **Задачі** з Asana | `asana:update-timestamps` | [Дивитись →](#asana-timestamps) |
| **Записи часу** з Masterok | `masterok:update-time-timestamps` | [Дивитись →](#masterok-timestamps) |
| Порівняти команди | - | [Дивитись →](#comparison) |

---

## <a name="asana-timestamps"></a>📋 Команда для задач (Asana)

### Коротко
```bash
php artisan asana:update-timestamps
```

Оновлює `created_at` і `updated_at` **задач** (`tasks`) з даними з **Asana API**.

### Швидкий старт
```bash
# Базове використання
php artisan asana:update-timestamps

# Масове оновлення
php artisan asana:update-timestamps --force --limit=1000

# Конкретна задача
php artisan asana:update-timestamps --task-id=376
```

### Особливості
- 📡 Джерело: Asana API через SDK
- 🗂️ Таблиця: `tasks`
- 🔄 Формат: ISO 8601 → MySQL
- 🔍 Зіставлення: по `gid`
- ⏱️ Швидкість: ~1-2 хв/100 записів

### 📚 Документація
- 📖 [Повна документація](./asana-update-timestamps-command.md)
- ⚡ [Швидка довідка](./asana-update-timestamps-quickref.md)
- 📝 [Резюме](../ASANA-TIMESTAMPS-SUMMARY.md)

---

## <a name="masterok-timestamps"></a>⏱️ Команда для записів часу (Masterok)

### Коротко
```bash
php artisan masterok:update-time-timestamps
```

Оновлює `created_at` і `updated_at` **записів часу** (`times`) з даними з **Masterok Market API**.

### Швидкий старт
```bash
# Базове використання
php artisan masterok:update-time-timestamps

# Масове оновлення
php artisan masterok:update-time-timestamps --force --limit=1000

# Конкретний запис
php artisan masterok:update-time-timestamps --time-id=123

# З кастомним URL
php artisan masterok:update-time-timestamps --url=https://custom-api.com/timer
```

### Особливості
- 📡 Джерело: Masterok Market API через HTTP
- 🗂️ Таблиця: `times`
- 🔄 Формат: MySQL (готовий)
- 🔍 Зіставлення: по `task_gid` + `duration`
- ⏱️ Швидкість: ~10-20 сек/100 записів

### 📚 Документація
- 📖 [Повна документація](./masterok-update-time-timestamps-command.md)
- ⚡ [Швидка довідка](./masterok-update-time-timestamps-quickref.md)
- 📝 [Резюме](../MASTEROK-TIMESTAMPS-SUMMARY.md)

---

## <a name="comparison"></a>🔀 Порівняння команд

### Основні відмінності

| Характеристика | asana:update-timestamps | masterok:update-time-timestamps |
|----------------|------------------------|--------------------------------|
| **Таблиця** | `tasks` | `times` |
| **API** | Asana SDK | Masterok Market HTTP |
| **Формат дат** | ISO 8601 → MySQL | MySQL (готовий) |
| **Зіставлення** | По `gid` | По `task_gid` + `duration` |
| **Швидкість** | ~1-2 хв/100 | ~10-20 сек/100 |

### 📖 [Детальне порівняння →](./timestamps-commands-comparison.md)

---

## 🚀 Типові сценарії використання

### Сценарій 1: Початкова синхронізація

```bash
# 1. Синхронізувати задачі з Asana
php artisan asana:sync

# 2. Оновити timestamps задач
php artisan asana:update-timestamps --force --limit=1000

# 3. Імпортувати дані таймера
php artisan app:fetch-timer-data-from-api --import

# 4. Оновити timestamps записів часу
php artisan masterok:update-time-timestamps --force --limit=1000
```

### Сценарій 2: Регулярне оновлення

```bash
# Щодня оновлювати тільки нові записи
php artisan asana:update-timestamps --limit=100
php artisan masterok:update-time-timestamps --limit=100
```

### Сценарій 3: Виправлення конкретних записів

```bash
# Виправити конкретну задачу
php artisan asana:update-timestamps --task-id=376

# Виправити конкретний запис часу
php artisan masterok:update-time-timestamps --time-id=123
```

---

## 📊 Статистика та перевірка

### Перевірка задач
```bash
# Скільки задач з Asana GID
php artisan tinker --execute="
echo 'Задач з GID: ' . \App\Models\Task::whereNotNull('gid')->count() . PHP_EOL;
"

# Скільки з різними timestamps
php artisan tinker --execute="
echo 'З різними timestamps: ' . 
\App\Models\Task::whereNotNull('gid')->whereRaw('created_at != updated_at')->count() . PHP_EOL;
"
```

### Перевірка записів часу
```bash
# Скільки записів з task->gid
php artisan tinker --execute="
echo 'Записів з task->gid: ' . 
\App\Models\Time::whereHas('task', function(\$q) { \$q->whereNotNull('gid'); })->count() . PHP_EOL;
"

# Скільки з різними timestamps
php artisan tinker --execute="
echo 'З різними timestamps: ' . 
\App\Models\Time::whereRaw('created_at != updated_at')->count() . PHP_EOL;
"
```

---

## 🧪 Тестування

### Запуск всіх тестів
```bash
php artisan test --filter=UpdateTaskTimestampsTest
php artisan test --filter=UpdateTimeTimestampsTest
```

### Запуск конкретного тесту
```bash
# Тест для задач
php artisan test --filter=test_command_updates_task_timestamps

# Тест для записів часу
php artisan test --filter=test_command_updates_time_timestamps
```

---

## 📅 Автоматизація (Scheduler)

Додати в `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

// Оновлення задач щодня о 3:00
Schedule::command('asana:update-timestamps --limit=50')
    ->daily()
    ->at('03:00');

// Оновлення записів часу щодня о 4:00
Schedule::command('masterok:update-time-timestamps --limit=50')
    ->daily()
    ->at('04:00');
```

---

## 🔧 Troubleshooting

### Asana API не відповідає
```bash
# Перевірити з'єднання
php artisan tinker --execute="
\$service = app(\App\Services\AsanaService::class);
try {
    \$projects = \$service->getProjects();
    echo 'Asana API працює!' . PHP_EOL;
} catch (\Exception \$e) {
    echo 'Помилка: ' . \$e->getMessage() . PHP_EOL;
}
"
```

### Masterok API не відповідає
```bash
# Перевірити доступність
curl https://asana.masterok-market.com.ua/admin/api/timer/list

# Або використати кастомний URL
php artisan masterok:update-time-timestamps --url=https://backup-api.com/timer
```

### Логи
```bash
# Переглянути логи оновлень
tail -f storage/logs/laravel.log | grep "timestamps"

# Фільтрувати по типу
tail -f storage/logs/laravel.log | grep "timestamps задачі"
tail -f storage/logs/laravel.log | grep "timestamps запису часу"
```

---

## 📚 Вся документація

### Команди
- [Огляд всіх команд API](./api-console-commands-overview.md)
- [Порівняння команд timestamps](./timestamps-commands-comparison.md)

### Asana (tasks)
- [Повна документація](./asana-update-timestamps-command.md)
- [Швидка довідка](./asana-update-timestamps-quickref.md)
- [Резюме](../ASANA-TIMESTAMPS-SUMMARY.md)

### Masterok (times)
- [Повна документація](./masterok-update-time-timestamps-command.md)
- [Швидка довідка](./masterok-update-time-timestamps-quickref.md)
- [Резюме](../MASTEROK-TIMESTAMPS-SUMMARY.md)

### Пов'язані команди
- [Timer API команда](./timer-api-command.md)
- [Asana інтеграція](./asana-integration-guide.md)
- [Asana синхронізація](./asana-artisan-commands.md)

---

## 🆘 Підтримка

**Логи:** `storage/logs/laravel.log`

**Тести:** 
- `tests/Feature/UpdateTaskTimestampsTest.php`
- `tests/Feature/UpdateTimeTimestampsTest.php`

**Команди:**
- `app/Console/Commands/UpdateTaskTimestamps.php`
- `app/Console/Commands/UpdateTimeTimestamps.php`

---

## ✅ Чек-лист впровадження

- [x] Створено команду `asana:update-timestamps`
- [x] Створено команду `masterok:update-time-timestamps`
- [x] Написано тести для обох команд
- [x] Створено повну документацію
- [x] Створено швидкі довідки
- [x] Створено порівняльну таблицю
- [x] Оновлено загальний огляд команд
- [ ] Запустити тести
- [ ] Протестувати на реальних даних
- [ ] Додати в scheduler (опціонально)

---

**Останнє оновлення:** 2025-10-28

