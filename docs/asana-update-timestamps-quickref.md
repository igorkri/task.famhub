# Asana Update Timestamps - Швидка довідка

## Швидкий старт

```bash
# Оновити задачі без правильних timestamps (рекомендовано)
php artisan asana:update-timestamps

# Оновити всі задачі
php artisan asana:update-timestamps --force --limit=1000

# Оновити одну задачу
php artisan asana:update-timestamps --task-id=376
```

## Опції

| Опція | Опис | Значення за замовчуванням |
|-------|------|---------------------------|
| `--task-id` | ID конкретної задачі | - |
| `--limit` | Максимальна кількість задач | 100 |
| `--force` | Оновити всі задачі | false |

## Що робить команда

1. ✅ Отримує дані з Asana API (`created_at`, `modified_at`)
2. ✅ Конвертує формат дат (ISO 8601 → MySQL)
3. ✅ Оновлює `created_at` і `updated_at` в таблиці `tasks`
4. ✅ Логує результати

## Типові сценарії

### Початкова синхронізація

```bash
php artisan asana:update-timestamps --force --limit=5000
```

### Регулярне оновлення

```bash
php artisan asana:update-timestamps --limit=100
```

### Виправлення однієї задачі

```bash
php artisan asana:update-timestamps --task-id=123
```

## Перевірка результатів

```bash
# Перевірити кількість оновлених задач
php artisan tinker --execute="
echo 'Задач з різними timestamps: ' . 
\App\Models\Task::whereNotNull('gid')->whereRaw('created_at != updated_at')->count();
"
```

## Логи

Логи зберігаються в `storage/logs/laravel.log`:

```
[INFO] Оновлено timestamps задачі {task_id, created_at, updated_at}
[ERROR] Помилка оновлення timestamps задачі {task_id, error}
```

## Продуктивність

- 100 задач: ~1-2 хвилини
- 1000 задач: ~10-15 хвилин
- API limit: 1500 запитів/хвилину

## Повна документація

Детальна інформація: [asana-update-timestamps-command.md](./asana-update-timestamps-command.md)

