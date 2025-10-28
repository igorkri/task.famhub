# Резюме: Команда обновления timestamps записей времени из Masterok Market API

## Что было сделано

✅ **Создана консольная команда** `masterok:update-time-timestamps`
- Файл: `app/Console/Commands/UpdateTimeTimestamps.php`
- Автоматически зарегистрирована в Laravel

✅ **Функциональность команды**
- Получает данные из Masterok Market API (`/admin/api/timer/list`)
- Индексирует данные по `task_gid` для быстрого поиска
- Сопоставляет записи по `task_gid` и `duration`
- Обновляет `created_at` и `updated_at` в таблице `times`
- Логирует все операции
- Показывает прогресс и статистику

✅ **Опции команды**
- `--time-id` - обновление конкретной записи времени
- `--limit=100` - ограничение количества записей (по умолчанию: 100)
- `--force` - обновление всех записей, даже с установленными timestamps
- `--url` - кастомный URL API

✅ **Документация**
- Полная документация: `docs/masterok-update-time-timestamps-command.md`
- Быстрая справка: `docs/masterok-update-time-timestamps-quickref.md`
- Обновлен обзор команд: `docs/api-console-commands-overview.md`

✅ **Тесты**
- Файл: `tests/Feature/UpdateTimeTimestampsTest.php`
- 7 тестовых сценариев:
  - Запись не найдена
  - API недоступен
  - Пустой ответ API
  - Успешное обновление timestamps
  - Пропуск записей без task_gid
  - Обработка нескольких записей с лимитом
  - Сопоставление по duration

## Использование

### Базовое использование (рекомендуется)
```bash
php artisan masterok:update-time-timestamps
```
Обновит до 100 записей, у которых `created_at` = `updated_at`

### Обновление конкретной записи
```bash
php artisan masterok:update-time-timestamps --time-id=123
```

### Массовое обновление всех записей
```bash
php artisan masterok:update-time-timestamps --force --limit=1000
```

### С кастомным URL
```bash
php artisan masterok:update-time-timestamps --url=https://custom-api.com/timer/list
```

## Технические детали

### API Endpoint
```
https://asana.masterok-market.com.ua/admin/api/timer/list
```

### Формат данных API
```json
{
  "id": 749,
  "task_gid": "1211692396550896",
  "time": "00:37:41",
  "minute": 37,
  "coefficient": 1,
  "comment": null,
  "status": 1,
  "archive": 0,
  "status_act": "not_ok",
  "created_at": "2025-10-23 12:13:51",
  "updated_at": "2025-10-23 14:43:28",
  "date_invoice": null,
  "date_report": null
}
```

### Алгоритм сопоставления
1. **Получение данных**: Загружает все записи из API
2. **Индексация**: Группирует по `task_gid`
3. **Поиск соответствия**:
   - Сопоставляет локальную запись по `task->gid`
   - Ищет точное совпадение по `duration`
   - Если нет точного - берет первую запись с тем же `task_gid`

### Расчет duration
```php
// API формат: "00:37:41"
$duration = strtotime($record['time']) - strtotime('TODAY');
// Результат: 2261 секунд
```

### Формат дат
API возвращает даты **уже в формате MySQL**:
```
2025-10-23 12:13:51
```
Конвертация не требуется!

### Обработка
- Использует `DB::table()` для прямого обновления (обходит Laravel timestamps)
- Обрабатывает ошибки индивидуально (не прерывает обработку других записей)
- Логирует успехи и ошибки в `storage/logs/laravel.log`

### Производительность
- Получение данных API: ~2-5 секунд
- ~100 записей: 10-20 секунд
- ~1000 записей: 1-2 минуты

## Отличия от команды для задач (asana:update-timestamps)

| Аспект | masterok:update-time-timestamps | asana:update-timestamps |
|--------|--------------------------------|-------------------------|
| **Источник данных** | Masterok Market API | Asana API |
| **Таблица** | `times` | `tasks` |
| **Формат дат** | MySQL (готовый) | ISO 8601 (нужна конвертация) |
| **Сопоставление** | task_gid + duration | gid |
| **API endpoint** | `/admin/api/timer/list` | Asana SDK |
| **Метод получения** | HTTP GET весь список | По ID через SDK |

## Проверка работы

### Просмотр команды в списке
```bash
php artisan list masterok
```

### Справка по команде
```bash
php artisan masterok:update-time-timestamps --help
```

### Запуск тестов
```bash
php artisan test --filter=UpdateTimeTimestampsTest
```

### Проверка результатов в базе
```bash
php artisan tinker --execute="
\$time = \App\Models\Time::find(123);
echo 'Created: ' . \$time->created_at . PHP_EOL;
echo 'Updated: ' . \$time->updated_at . PHP_EOL;
"
```

## Файлы проекта

### Основные файлы
- `app/Console/Commands/UpdateTimeTimestamps.php` - команда
- `tests/Feature/UpdateTimeTimestampsTest.php` - тесты

### Документация
- `docs/masterok-update-time-timestamps-command.md` - полная документация
- `docs/masterok-update-time-timestamps-quickref.md` - быстрая справка
- `docs/api-console-commands-overview.md` - обновленный обзор

## Интеграция с другими командами

Рекомендуемая последовательность:
```bash
# 1. Импорт данных таймера
php artisan app:fetch-timer-data-from-api --import

# 2. Обновление timestamps
php artisan masterok:update-time-timestamps --force --limit=1000
```

## Автоматизация (опционально)

Добавить в планировщик Laravel:
```php
// routes/console.php
Schedule::command('masterok:update-time-timestamps --limit=50')
    ->daily()
    ->at('04:00');
```

## Статистика базы данных

```bash
# Всего записей времени
php artisan tinker --execute="echo \App\Models\Time::count();"

# Записей с task->gid
php artisan tinker --execute="
echo \App\Models\Time::whereHas('task', function(\$q) {
    \$q->whereNotNull('gid');
})->count();
"

# Записей с разными timestamps
php artisan tinker --execute="
echo \App\Models\Time::whereRaw('created_at != updated_at')->count();
"
```

## Troubleshooting

### API не отвечает
```bash
# Проверить доступность
curl https://asana.masterok-market.com.ua/admin/api/timer/list

# Использовать кастомный URL
php artisan masterok:update-time-timestamps --url=https://backup-api.com/timer/list
```

### Записи не обновляются
Проверить, что у задач есть `gid`:
```bash
php artisan tinker --execute="
echo 'Записей без task->gid: ' . 
\App\Models\Time::whereHas('task', function(\$q) {
    \$q->whereNull('gid');
})->count();
"
```

### Несовпадение duration
Проверить расчет duration:
```bash
php artisan tinker --execute="
\$time = \App\Models\Time::first();
echo 'Duration: ' . \$time->duration . ' секунд' . PHP_EOL;
echo 'В часах: ' . gmdate('H:i:s', \$time->duration) . PHP_EOL;
"
```

## Следующие шаги

1. ✅ **Запустить тесты:**
   ```bash
   php artisan test --filter=UpdateTimeTimestampsTest
   ```

2. ✅ **Протестировать на одной записи:**
   ```bash
   php artisan masterok:update-time-timestamps --time-id=1
   ```

3. ✅ **Массовое обновление (если нужно):**
   ```bash
   php artisan masterok:update-time-timestamps --force --limit=100
   ```

4. ✅ **Проверить результаты в логах:**
   ```bash
   tail -f storage/logs/laravel.log | grep "timestamps запису часу"
   ```

## Сравнение двух команд

| Команда | Таблица | API | Использование |
|---------|---------|-----|---------------|
| `asana:update-timestamps` | tasks | Asana API | Задачи из Asana |
| `masterok:update-time-timestamps` | times | Masterok Market API | Записи времени из таймера |

Обе команды работают независимо и могут использоваться параллельно:

```bash
# Обновить задачи
php artisan asana:update-timestamps --force --limit=500

# Обновить записи времени
php artisan masterok:update-time-timestamps --force --limit=500
```

## Поддержка

- Документация: `docs/masterok-update-time-timestamps-command.md`
- Быстрая справка: `docs/masterok-update-time-timestamps-quickref.md`
- Логи: `storage/logs/laravel.log`
- API документация: `docs/timer-api-command.md`

