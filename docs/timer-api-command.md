# Команда для получения данных таймера через API

## Описание

Консольная команда `app:fetch-timer-data-from-api` предназначена для получения данных таймера из внешнего API и импорта их в базу данных.

## Использование

### Базовое использование

Получить данные с URL по умолчанию:

```bash
php artisan app:fetch-timer-data-from-api
```

### Импорт данных в базу данных

#### Импорт без очистки таблицы

```bash
php artisan app:fetch-timer-data-from-api --import
```

Эта команда импортирует данные из API напрямую в таблицу `times`. Если запись с такими же параметрами (`task_id`, `duration`, `created_at`) уже существует, она будет обновлена.

#### Импорт с очисткой таблицы

```bash
php artisan app:fetch-timer-data-from-api --import --truncate
```

**⚠️ ВНИМАНИЕ:** Эта команда удалит ВСЕ записи из таблицы `times` перед импортом!

При запуске вручную команда запросит подтверждение. Чтобы пропустить подтверждение (например, в автоматизированных скриптах), используйте флаг `--no-interaction`:

```bash
php artisan app:fetch-timer-data-from-api --import --truncate --no-interaction
```

### Опции

#### `--url`
Указать собственный URL для API:

```bash
php artisan app:fetch-timer-data-from-api --url=https://api.example.com/timer/data
```

#### `--import`
Импортировать данные в базу данных:

```bash
php artisan app:fetch-timer-data-from-api --import
```

#### `--truncate`
Очистить таблицу `times` перед импортом (используется только вместе с `--import`):

```bash
php artisan app:fetch-timer-data-from-api --import --truncate
```

#### `--save`
Сохранить полученные данные в JSON файл в `storage/app/`:

```bash
php artisan app:fetch-timer-data-from-api --save
```

Файл будет сохранён с именем в формате: `timer-api-YYYY-MM-DD_HH-mm-ss.json`

#### `--format`
Выбрать формат вывода (`json` или `table`):

```bash
# Вывод в виде JSON (по умолчанию)
php artisan app:fetch-timer-data-from-api --format=json

# Вывод в виде таблицы
php artisan app:fetch-timer-data-from-api --format=table
```

### Комбинированное использование

```bash
# Импортировать данные, показать таблицу и сохранить в файл
php artisan app:fetch-timer-data-from-api --import --format=table --save

# Полная очистка и новый импорт с сохранением
php artisan app:fetch-timer-data-from-api --import --truncate --save --no-interaction
```

## Логика импорта

### Маппинг полей API → База данных

| Поле API | Поле БД | Описание |
|----------|---------|----------|
| `task_gid` | `task_id` | GID задачи в Asana (ищется в таблице tasks) |
| `minutes` | `duration` | Длительность в минутах → секунды |
| `coefficient` | `coefficient` | Коэффициент (по умолчанию 1.0) |
| `status` | `status` | Статус (см. маппинг статусов ниже) |
| `status_act` | `report_status` | Статус отчета (см. маппинг ниже) |
| `comment` | `description` | Комментарий |
| `archive` | `is_archived` | Архивный статус |
| `created_at` | `created_at` | Дата создания |
| `updated_at` | `updated_at` | Дата обновления |

### Маппинг статусов

**status** (из API → в БД):
- `0` → `completed` (выполнено)
- `1` → `in_progress` (в процессе)
- `2` → `planned` (запланировано)
- `3`, `4` → `export_akt` (экспорт акта)
- `5` → `needs_clarification` (требует уточнения)
- Другое → `new` (новый)

**status_act** (из API → в БД):
- `ok` → `submitted` (подано)
- `not_ok` → `not_submitted` (не подано)
- `null` → `not_submitted` (по умолчанию)

### Правила импорта

1. **Обязательные поля:**
   - `task_gid` - должен присутствовать в API данных
   - Задача с этим GID должна существовать в базе
   - У задачи должен быть назначен `user_id`

2. **Пропускаемые записи:**
   - Записи без `task_gid`
   - Записи для несуществующих задач
   - Записи для задач без назначенного пользователя

3. **Уникальность:**
   - Запись считается уникальной по комбинации: `task_id` + `duration` + `created_at`
   - При совпадении запись обновляется, а не дублируется

4. **Прогресс:**
   - Команда показывает прогресс-бар во время импорта
   - После завершения выводится статистика: импортировано / пропущено / ошибки

## Конфигурация

URL API по умолчанию можно настроить в файле `.env`:

```env
TIMER_API_URL=https://asana.masterok-market.com.ua/admin/api/timer/list
TIMER_API_TOKEN=your_token_here
```

Если переменные не установлены, используется URL по умолчанию из `config/services.php`.

## Примеры

### Пример 1: Просмотр данных перед импортом

```bash
php artisan app:fetch-timer-data-from-api --format=table
```

### Пример 2: Импорт новых данных

```bash
php artisan app:fetch-timer-data-from-api --import
```

### Пример 3: Полное обновление данных (с очисткой)

```bash
php artisan app:fetch-timer-data-from-api --import --truncate
# Подтвердите удаление при запросе
```

### Пример 4: Автоматический импорт (без подтверждения)

```bash
php artisan app:fetch-timer-data-from-api --import --truncate --no-interaction
```

### Пример 5: Импорт с сохранением копии

```bash
php artisan app:fetch-timer-data-from-api --import --save
```

## Возвращаемые коды

- `0` (SUCCESS) - Данные успешно получены/импортированы
- `1` (FAILURE) - Ошибка при получении данных

## Обработка ошибок

Команда обрабатывает следующие ситуации:
- Таймаут запроса (30 секунд)
- Ошибки HTTP (неуспешный статус код)
- Пустой ответ от API
- Исключения при запросе
- Ошибки при импорте отдельных записей (не прерывают весь процесс)

Все ошибки выводятся в консоль с подробным описанием.

## Автоматизация

### Cron задача для регулярного импорта

Добавьте в `routes/console.php` или настройте cron:

```php
Schedule::command('app:fetch-timer-data-from-api --import --no-interaction')
    ->hourly()
    ->emailOutputOnFailure('admin@example.com');
```

### Bash скрипт для ежедневного обновления

```bash
#!/bin/bash
# daily-timer-import.sh

echo "Starting daily timer data import..."
php artisan app:fetch-timer-data-from-api --import --truncate --no-interaction --save

if [ $? -eq 0 ]; then
    echo "✅ Import completed successfully"
else
    echo "❌ Import failed"
    exit 1
fi
```

## Мониторинг импорта

После импорта проверьте статистику:

```bash
php artisan tinker
>>> Time::count()  // Общее количество записей
>>> Time::whereDate('created_at', today())->count()  // Импортировано сегодня
>>> Time::where('is_archived', true)->count()  // Архивные записи
```

## Связанные команды

- `php artisan app:import-timer-csv` - импорт из CSV файла
- `php artisan app:fetch-act-of-work-list-from-api` - получение списка актов
- `php artisan app:fetch-act-of-work-detail-from-api` - получение деталей акта
