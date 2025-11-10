# Мониторинг Графиков Отключений Электроэнергии

## Быстрый старт

### 1. Настройка Telegram

Добавьте в `.env`:

```env
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHAT_ID=your_chat_id_here
```

Или используйте интерактивный скрипт:

```bash
./scripts/setup-telegram.sh
```

### 2. Тестирование

```bash
# Запустить тестовый скрипт
./scripts/test-power-outage.sh

# Вручную получить график
php artisan power:fetch-schedule

# Получить график на конкретную дату
php artisan power:fetch-schedule 09-11-2025
```

### 3. Автоматический мониторинг

Система автоматически проверяет график каждые 10 минут через Laravel Scheduler.

**📖 Детальна інструкція:** [AUTO-SCHEDULE-SETUP.md](AUTO-SCHEDULE-SETUP.md) | [Швидкий довідник](AUTO-SCHEDULE-QUICKREF.md)

Убедитесь, что настроен cron:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Для production рекомендуется использовать Supervisor или systemd timer.

## Возможности

✅ Автоматическое получение данных каждые 10 минут  
✅ Парсинг HTML в структурированные данные  
✅ Сохранение истории изменений  
✅ Уведомления в Telegram при изменении графика  
✅ Детальная информация по очередям и временным интервалам  

## Структура данных

```php
PowerOutageSchedule {
    schedule_date: '2025-11-08'        // Дата графика
    description: 'У зв\'язку зі...'    // Описание
    periods: [                          // Периоды отключений
        {from: '07:00', to: '16:00', queues: 2.5},
        {from: '16:00', to: '23:59', queues: 4}
    ]
    schedule_data: [                    // Детальное расписание
        {
            queue: '1 черга',
            subqueue: '1',
            hourly_status: ['on', 'off', 'maybe', ...] // 48 элементов
        },
        ...
    ]
}
```

## Команды

```bash
# Получить график
php artisan power:fetch-schedule [date]

# Запустить тесты
php artisan test --filter=PowerOutageScheduleTest

# Просмотр планировщика
php artisan schedule:list

# Проверка логов
tail -f storage/logs/laravel.log | grep -i power
```

## Документация

Полная документация: [docs/power-outage-monitor.md](docs/power-outage-monitor.md)

## API

**Endpoint:** `https://www.poe.pl.ua/customs/newgpv-info.php`  
**Method:** `POST`  
**Body:** `seldate={"date_in":"DD-MM-YYYY"}`

## Структура файлов

- `app/Console/Commands/FetchPowerOutageSchedule.php` - Команда получения данных
- `app/Jobs/SendPowerOutageNotification.php` - Job для отправки уведомлений
- `app/Models/PowerOutageSchedule.php` - Модель расписания
- `app/Services/PowerOutageParserService.php` - Сервис парсинга HTML
- `tests/Feature/PowerOutageScheduleTest.php` - Тесты
- `test-power-outage.sh` - Скрипт для тестирования

## Примеры использования

### Программный доступ

```php
use App\Models\PowerOutageSchedule;

// Последний график
$schedule = PowerOutageSchedule::latest('fetched_at')->first();

// График на конкретную дату
$schedule = PowerOutageSchedule::whereDate('schedule_date', '2025-11-08')
    ->latest('fetched_at')
    ->first();

// История изменений за день
$changes = PowerOutageSchedule::whereDate('schedule_date', today())
    ->orderBy('fetched_at')
    ->get();
```

### Анализ расписания

```php
foreach ($schedule->schedule_data as $row) {
    $offCount = count(array_filter($row['hourly_status'], fn($s) => $s === 'off'));
    $offHours = $offCount / 2; // 2 получаса = 1 час
    
    echo "{$row['queue']}.{$row['subqueue']}: {$offHours}ч отключений\n";
}
```

## Уведомления Telegram

При изменении графика автоматически отправляется сообщение:

```
🔌 Оновлення графіку відключень

📅 Дата: 08.11.2025

У зв'язку зі складною ситуацією в енергосистемі України...

⏰ Періоди:
• 07:00 - 16:00: 2.5 черг
• 16:00 - 23:59: 4 черг

📊 Черги:
• 1 черга: 9г вимк.
• 2 черга: 9.5г вимк.
...
```

## Лицензия

Внутренний проект

