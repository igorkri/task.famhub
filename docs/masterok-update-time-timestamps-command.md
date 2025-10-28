# Оновлення Timestamps Записів Часу з Masterok Market API

## Опис

Консольна команда `masterok:update-time-timestamps` оновлює поля `created_at` і `updated_at` записів часу у локальній базі даних з даними з Masterok Market API.

**Важливо:** Ця команда працює з **Masterok Market API**, а не з Asana API!

## Використання

### Базове використання

```bash
php artisan masterok:update-time-timestamps
```

За замовчуванням команда оновлює до 100 записів часу, у яких `created_at` дорівнює `updated_at` (що означає, що timestamps ще не були встановлені з API).

### Опції

#### `--time-id`

Оновити конкретний запис часу за його ID в локальній базі даних.

```bash
php artisan masterok:update-time-timestamps --time-id=123
```

#### `--limit`

Максимальна кількість записів для оновлення за один запуск (за замовчуванням: 100).

```bash
php artisan masterok:update-time-timestamps --limit=500
```

#### `--force`

Оновити всі записи часу, навіть якщо timestamps вже були встановлені.

```bash
php artisan masterok:update-time-timestamps --force
```

#### `--url`

Вказати кастомний URL API (за замовчуванням: `https://asana.masterok-market.com.ua/admin/api/timer/list`)

```bash
php artisan masterok:update-time-timestamps --url=https://api.example.com/timer/list
```

### Комбіновані приклади

Оновити всі записи з обмеженням 1000:

```bash
php artisan masterok:update-time-timestamps --force --limit=1000
```

Оновити конкретний запис з примусовим оновленням:

```bash
php artisan masterok:update-time-timestamps --time-id=123 --force
```

Використати кастомний URL API:

```bash
php artisan masterok:update-time-timestamps --url=https://custom-api.com/timer --limit=200
```

## Що робить команда

1. **Отримує дані з Masterok Market API**:
   - Викликає endpoint `/admin/api/timer/list`
   - Отримує всі записи часу з API

2. **Індексує дані API**:
   - Створює індекс по `task_gid` для швидкого пошуку
   - Групує записи по task_gid

3. **Вибирає записи** для оновлення:
   - Якщо вказано `--time-id`, обробляється лише цей запис
   - Без `--force`: вибирає записи, де `created_at` = `updated_at`
   - З `--force`: вибирає всі записи часу

4. **Знаходить відповідність** між локальними записами та API:
   - Порівнює по `task_gid` та `duration`
   - Шукає точне співпадіння тривалості

5. **Оновлює базу даних**:
   - Використовує `DB::table()` для прямого оновлення
   - Обходить автоматичне оновлення timestamps Laravel
   - Зберігає точні дати з API

6. **Логує результати**:
   - Успішні оновлення записуються в лог
   - Помилки також записуються з деталями

## Формат даних API

Команда очікує дані у форматі:

```json
[
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
]
```

### Важливі поля

- `task_gid` - GID задачі в Asana (використовується для зіставлення)
- `time` - тривалість у форматі HH:MM:SS
- `created_at` - дата створення запису
- `updated_at` - дата оновлення запису

## Вивід команди

Команда показує:
- 🕐 Початок роботи
- 📡 URL API та статус отримання даних
- ✅ Кількість отриманих записів з API
- 📅 Режим роботи (нормальний або force)
- 📦 Кількість знайдених записів для оновлення
- Прогрес-бар обробки
- ✅ Кількість оновлених записів
- ⚠️ Кількість пропущених записів (без даних)
- ❌ Кількість помилок
- 🎉 Завершення роботи

## Приклад виводу

```
🕐 Запуск оновлення часових міток записів часу з Masterok Market API...
📡 Отримання даних з API: https://asana.masterok-market.com.ua/admin/api/timer/list
✅ Отримано записів з API: 150
📅 Оновлюємо записи, де timestamps не встановлено з API
📦 Знайдено записів для оновлення: 45
 45/45 [============================] 100%

✅ Оновлено: 42
⚠️ Пропущено (немає даних): 3

🎉 Оновлення завершено!
```

## Коли використовувати

### Початкова синхронізація

Після імпорту записів часу з Masterok Market API, коли потрібно встановити правильні дати:

```bash
php artisan masterok:update-time-timestamps --force --limit=1000
```

### Регулярне оновлення

Для оновлення записів, які були створені/оновлені, але ще не мають правильних timestamps:

```bash
php artisan masterok:update-time-timestamps --limit=100
```

### Виправлення конкретного запису

Якщо потрібно виправити timestamps для конкретного запису:

```bash
php artisan masterok:update-time-timestamps --time-id=123
```

### Масове оновлення

Для оновлення всіх записів (наприклад, після міграції):

```bash
php artisan masterok:update-time-timestamps --force --limit=5000
```

## Алгоритм зіставлення

Команда використовує наступний алгоритм для знаходження відповідних записів:

1. **Пошук по task_gid**: Знаходить всі API записи з таким же `task_gid`
2. **Пошук по duration**: Шукає запис з точно таким же `duration` (у секундах)
3. **Fallback**: Якщо точного співпадіння немає, використовує перший знайдений запис

### Розрахунок duration з API

```php
$apiDuration = strtotime($record['time']) - strtotime('TODAY');
// Приклад: "00:37:41" => 2261 секунд
```

## Логування

Команда записує детальні логи в `storage/logs/laravel.log`:

### Успішне оновлення

```
[2025-10-28] local.INFO: Оновлено timestamps запису часу
{
    "time_id": 123,
    "task_id": 456,
    "task_gid": "1211692396550896",
    "created_at": "2025-10-23 12:13:51",
    "updated_at": "2025-10-23 14:43:28"
}
```

### Помилка оновлення

```
[2025-10-28] local.ERROR: Помилка оновлення timestamps запису часу
{
    "time_id": 123,
    "task_id": 456,
    "error": "API connection failed"
}
```

## Технічні деталі

### Формат дат

API повертає дати вже у форматі MySQL:
```
2025-10-23 12:13:51
```

Ці дати можуть бути використані безпосередньо без конвертації.

### Прямий UPDATE

Команда використовує `DB::table()->update()` замість `Model::update()`, щоб:
- Обійти автоматичне оновлення `updated_at` Laravel
- Зберегти точні дати з API
- Підвищити продуктивність для масових оновлень

### Обробка помилок

- API помилки логуються і не зупиняють обробку інших записів
- Записи без відповідних task_gid пропускаються
- Записи без дат в API пропускаються
- Неіснуючі записи повертають помилку

## Відмінності від asana:update-timestamps

| Аспект | masterok:update-time-timestamps | asana:update-timestamps |
|--------|--------------------------------|-------------------------|
| Джерело даних | Masterok Market API | Asana API |
| Таблиця | `times` | `tasks` |
| Формат дат | MySQL (готовий) | ISO 8601 (потрібна конвертація) |
| Зіставлення | task_gid + duration | gid |
| API endpoint | `/admin/api/timer/list` | Asana SDK |

## Планування

Можна додати команду в планувальник для регулярного оновлення:

```php
// routes/console.php

Schedule::command('masterok:update-time-timestamps --limit=50')
    ->daily()
    ->at('04:00');
```

## Зв'язок з іншими командами

Ця команда добре працює разом з:

- `app:fetch-timer-data-from-api` - імпорт даних таймера
- `app:fetch-timer-data-from-api --import --truncate` - повний реімпорт

Рекомендований порядок:

```bash
# 1. Імпортувати дані таймера з API
php artisan app:fetch-timer-data-from-api --import

# 2. Оновити timestamps з правильними датами
php artisan masterok:update-time-timestamps --force --limit=1000
```

## Продуктивність

- Отримання даних з API: ~2-5 секунд
- Обробка 100 записів: ~10-20 секунд
- Обробка 1000 записів: ~1-2 хвилини
- Залежить від швидкості API та кількості записів

## Перевірка результатів

Після виконання команди можна перевірити результати:

```bash
# Перевірити кількість записів з різними timestamps
php artisan tinker --execute="
echo 'Записів з різними timestamps: ' . 
\App\Models\Time::whereRaw('created_at != updated_at')->count() . PHP_EOL;
"

# Перевірити конкретний запис
php artisan tinker --execute="
\$time = \App\Models\Time::find(123);
echo 'Created: ' . \$time->created_at . PHP_EOL;
echo 'Updated: ' . \$time->updated_at . PHP_EOL;
"

# Перевірити всі записи без task_gid
php artisan tinker --execute="
echo 'Записів без task->gid: ' . 
\App\Models\Time::whereHas('task', function(\$q) {
    \$q->whereNull('gid');
})->count() . PHP_EOL;
"
```

## Troubleshooting

### API не відповідає

```bash
# Перевірити доступність API
curl https://asana.masterok-market.com.ua/admin/api/timer/list

# Використати кастомний URL
php artisan masterok:update-time-timestamps --url=https://backup-api.com/timer/list
```

### Записи не знайдені

Перевірити, чи записи мають відповідні задачі з `gid`:

```bash
php artisan tinker --execute="
\$withoutGid = \App\Models\Time::whereHas('task', function(\$q) {
    \$q->whereNull('gid');
})->count();
echo 'Записів без task->gid: ' . \$withoutGid . PHP_EOL;
"
```

### Повільна робота

Збільшити timeout для API:

```php
// У команді змінити:
Http::timeout(60)->get($url);  // замість timeout(30)
```

Або зменшити limit:

```bash
php artisan masterok:update-time-timestamps --limit=50
```

