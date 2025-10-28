# Порівняння команд оновлення timestamps

## Дві команди для різних джерел даних

У проекті є дві окремі команди для оновлення timestamps:

1. **`asana:update-timestamps`** - для таблиці `tasks` (з Asana API)
2. **`masterok:update-time-timestamps`** - для таблиці `times` (з Masterok Market API)

---

## Швидке порівняння

| Характеристика | asana:update-timestamps | masterok:update-time-timestamps |
|----------------|------------------------|--------------------------------|
| **Таблиця** | `tasks` | `times` |
| **Джерело даних** | Asana API | Masterok Market API |
| **API тип** | Asana SDK (по ID) | HTTP REST (весь список) |
| **Формат дат** | ISO 8601 → MySQL | MySQL (готовий) |
| **Зіставлення** | По `gid` | По `task_gid` + `duration` |
| **Конвертація дат** | Потрібна | Не потрібна |
| **Продуктивність** | ~1-2 хв/100 записів | ~10-20 сек/100 записів |

---

## Команда 1: asana:update-timestamps

### Використання
```bash
php artisan asana:update-timestamps [опції]
```

### Таблиця
`tasks` - задачі з Asana

### Джерело даних
**Asana API** через Asana SDK:
- Отримує дані по кожній задачі окремо
- Викликає `AsanaService::getTaskDetails($gid)`
- Використовує офіційний Asana SDK

### Формат дат з API
```
Вхід: "2022-07-27T11:38:56.498Z" (ISO 8601)
Вихід: "2022-07-27 11:38:56" (MySQL)
```

**Потрібна конвертація:**
```php
$createdAt = \Carbon\Carbon::parse($taskDetails['created_at']);
$updateData['created_at'] = $createdAt->format('Y-m-d H:i:s');
```

### Алгоритм зіставлення
1. Знаходить локальну задачу по `id`
2. Використовує `task->gid` для запиту до Asana API
3. Отримує `created_at` та `modified_at` з API
4. Оновлює timestamps

### Опції
```bash
--task-id=123    # ID конкретної задачі
--limit=100      # Максимальна кількість (за замовчуванням: 100)
--force          # Оновити всі задачі
```

### Приклади
```bash
# Оновити задачі без правильних timestamps
php artisan asana:update-timestamps

# Оновити конкретну задачу
php artisan asana:update-timestamps --task-id=376

# Масове оновлення
php artisan asana:update-timestamps --force --limit=1000
```

### Документація
- Повна: `docs/asana-update-timestamps-command.md`
- Швидка: `docs/asana-update-timestamps-quickref.md`

---

## Команда 2: masterok:update-time-timestamps

### Використання
```bash
php artisan masterok:update-time-timestamps [опції]
```

### Таблиця
`times` - записи часу з таймера

### Джерело даних
**Masterok Market API** через HTTP:
- Отримує ВЕСЬ список записів одним запитом
- URL: `https://asana.masterok-market.com.ua/admin/api/timer/list`
- Використовує HTTP Client Laravel

### Формат дат з API
```
Вхід: "2025-10-23 12:13:51" (MySQL формат)
Вихід: "2025-10-23 12:13:51" (без змін)
```

**Конвертація НЕ потрібна:**
```php
$updateData['created_at'] = $apiRecord['created_at'];
$updateData['updated_at'] = $apiRecord['updated_at'];
```

### Алгоритм зіставлення
1. Отримує всі записи з API
2. Індексує їх по `task_gid`
3. Для локального запису знаходить `task->gid`
4. Шукає відповідність по `task_gid` + `duration`
5. Оновлює timestamps

### Опції
```bash
--time-id=123    # ID конкретного запису часу
--limit=100      # Максимальна кількість (за замовчуванням: 100)
--force          # Оновити всі записи
--url=...        # Кастомний URL API
```

### Приклади
```bash
# Оновити записи без правильних timestamps
php artisan masterok:update-time-timestamps

# Оновити конкретний запис
php artisan masterok:update-time-timestamps --time-id=123

# Масове оновлення
php artisan masterok:update-time-timestamps --force --limit=1000

# З кастомним URL
php artisan masterok:update-time-timestamps --url=https://custom-api.com/timer/list
```

### Документація
- Повна: `docs/masterok-update-time-timestamps-command.md`
- Швидка: `docs/masterok-update-time-timestamps-quickref.md`

---

## Детальне порівняння

### 1. Отримання даних з API

**asana:update-timestamps:**
```php
// По кожній задачі окремо
foreach ($tasks as $task) {
    $taskDetails = $service->getTaskDetails($task->gid);
    // created_at: "2022-07-27T11:38:56.498Z"
    // modified_at: "2022-08-14T09:39:24.629Z"
}
```

**masterok:update-time-timestamps:**
```php
// Весь список одним запитом
$response = Http::timeout(30)->get($url);
$apiData = $response->json();
// [
//   { task_gid, time, created_at, updated_at },
//   { task_gid, time, created_at, updated_at },
//   ...
// ]
```

### 2. Конвертація дат

**asana:update-timestamps:**
```php
// ISO 8601 → MySQL
$createdAt = \Carbon\Carbon::parse($taskDetails['created_at']);
$updateData['created_at'] = $createdAt->format('Y-m-d H:i:s');
```

**masterok:update-time-timestamps:**
```php
// Вже MySQL формат, конвертація не потрібна
$updateData['created_at'] = $apiRecord['created_at'];
```

### 3. Зіставлення записів

**asana:update-timestamps:**
```php
// Пряме зіставлення по gid
$taskDetails = $service->getTaskDetails($task->gid);
```

**masterok:update-time-timestamps:**
```php
// Складне зіставлення
$taskGid = $time->task?->gid;
$apiRecords = $apiDataByTaskGid[$taskGid] ?? [];

// Шукаємо по duration
foreach ($apiRecords as $record) {
    $apiDuration = strtotime($record['time']) - strtotime('TODAY');
    if ($apiDuration === $time->duration) {
        return $record; // Знайшли точне співпадіння
    }
}
```

### 4. Продуктивність

**asana:update-timestamps:**
- 100 задач: ~1-2 хвилини
- Кожна задача = окремий API запит
- Обмеження: Asana API rate limit (1500 req/min)

**masterok:update-time-timestamps:**
- 100 записів: ~10-20 секунд
- 1 API запит для всіх записів
- Швидше завдяки єдиному запиту

---

## Коли використовувати кожну команду

### asana:update-timestamps

**Використовувати для:**
- ✅ Оновлення timestamps задач (`tasks`)
- ✅ Синхронізації з Asana
- ✅ Після створення нових задач в Asana
- ✅ Коли потрібні дати створення/модифікації задач

**Приклад сценарію:**
```bash
# 1. Синхронізувати задачі з Asana
php artisan asana:sync

# 2. Оновити їх timestamps
php artisan asana:update-timestamps --force --limit=500
```

### masterok:update-time-timestamps

**Використовувати для:**
- ✅ Оновлення timestamps записів часу (`times`)
- ✅ Синхронізації з Masterok Market API
- ✅ Після імпорту даних таймера
- ✅ Коли потрібні дати створення/модифікації записів часу

**Приклад сценарію:**
```bash
# 1. Імпортувати дані таймера
php artisan app:fetch-timer-data-from-api --import

# 2. Оновити їх timestamps
php artisan masterok:update-time-timestamps --force --limit=500
```

---

## Паралельне використання

Обидві команди можна використовувати разом:

```bash
# Повна синхронізація обох джерел
php artisan asana:sync
php artisan asana:update-timestamps --force --limit=1000

php artisan app:fetch-timer-data-from-api --import
php artisan masterok:update-time-timestamps --force --limit=1000
```

---

## Спільні характеристики

Обидві команди:
- ✅ Використовують `DB::table()` для прямого оновлення
- ✅ Обходять автоматичне оновлення Laravel timestamps
- ✅ Логують успіхи та помилки
- ✅ Показують прогрес-бар
- ✅ Підтримують `--limit` та `--force`
- ✅ Мають детальну документацію
- ✅ Мають автоматичні тести

---

## Резюме

| Якщо потрібно оновити... | Використовуйте команду... |
|--------------------------|---------------------------|
| Timestamps **задач** з Asana | `asana:update-timestamps` |
| Timestamps **записів часу** з Masterok | `masterok:update-time-timestamps` |
| Обидва | Обидві команди послідовно |

---

## Швидка довідка

```bash
# Задачі (Asana)
php artisan asana:update-timestamps --help
php artisan asana:update-timestamps --force --limit=100

# Записи часу (Masterok)
php artisan masterok:update-time-timestamps --help
php artisan masterok:update-time-timestamps --force --limit=100
```

---

## Документація

### Команда для задач (Asana)
- 📖 [asana-update-timestamps-command.md](./asana-update-timestamps-command.md)
- ⚡ [asana-update-timestamps-quickref.md](./asana-update-timestamps-quickref.md)
- 📝 [ASANA-TIMESTAMPS-SUMMARY.md](../ASANA-TIMESTAMPS-SUMMARY.md)

### Команда для записів часу (Masterok)
- 📖 [masterok-update-time-timestamps-command.md](./masterok-update-time-timestamps-command.md)
- ⚡ [masterok-update-time-timestamps-quickref.md](./masterok-update-time-timestamps-quickref.md)
- 📝 [MASTEROK-TIMESTAMPS-SUMMARY.md](../MASTEROK-TIMESTAMPS-SUMMARY.md)

### Загальна документація
- 📚 [api-console-commands-overview.md](./api-console-commands-overview.md)

