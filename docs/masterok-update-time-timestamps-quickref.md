# Masterok Update Time Timestamps - Швидка довідка

## Швидкий старт

```bash
# Оновити записи без правильних timestamps (рекомендовано)
php artisan masterok:update-time-timestamps

# Оновити всі записи
php artisan masterok:update-time-timestamps --force --limit=1000

# Оновити один запис
php artisan masterok:update-time-timestamps --time-id=123
```

## Опції

| Опція | Опис | Значення за замовчуванням |
|-------|------|---------------------------|
| `--time-id` | ID конкретного запису часу | - |
| `--limit` | Максимальна кількість записів | 100 |
| `--force` | Оновити всі записи | false |
| `--url` | Кастомний URL API | config value |

## Що робить команда

1. ✅ Отримує дані з Masterok Market API
2. ✅ Індексує дані по `task_gid`
3. ✅ Зіставляє по `task_gid` + `duration`
4. ✅ Оновлює `created_at` і `updated_at` в таблиці `times`
5. ✅ Логує результати

## Типові сценарії

### Початкова синхронізація

```bash
php artisan masterok:update-time-timestamps --force --limit=5000
```

### Регулярне оновлення

```bash
php artisan masterok:update-time-timestamps --limit=100
```

### Виправлення одного запису

```bash
php artisan masterok:update-time-timestamps --time-id=123
```

## Перевірка результатів

```bash
# Перевірити кількість оновлених записів
php artisan tinker --execute="
echo 'Записів з різними timestamps: ' . 
\App\Models\Time::whereRaw('created_at != updated_at')->count();
"
```

## API Endpoint

```
https://asana.masterok-market.com.ua/admin/api/timer/list
```

## Формат даних API

```json
{
  "task_gid": "1211692396550896",
  "time": "00:37:41",
  "created_at": "2025-10-23 12:13:51",
  "updated_at": "2025-10-23 14:43:28"
}
```

## Логи

```
storage/logs/laravel.log
```

## Зв'язок з іншими командами

```bash
# 1. Імпортувати дані з API
php artisan app:fetch-timer-data-from-api --import

# 2. Оновити timestamps
php artisan masterok:update-time-timestamps --force --limit=1000
```

## Продуктивність

- 100 записів: ~10-20 секунд
- 1000 записів: ~1-2 хвилини

## Відмінності від Asana команди

| Аспект | Masterok (times) | Asana (tasks) |
|--------|------------------|---------------|
| API | Masterok Market | Asana SDK |
| Таблиця | `times` | `tasks` |
| Формат дат | MySQL готовий | ISO 8601 → MySQL |
| Зіставлення | task_gid + duration | gid |

## Повна документація

Детальна інформація: [masterok-update-time-timestamps-command.md](./masterok-update-time-timestamps-command.md)

