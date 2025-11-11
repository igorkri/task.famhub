# Air Alert Service - Інтеграція з API повітряних тривог України

## Опис

`AirAlertService` - сервіс для роботи з API alerts.in.ua, який надає інформацію про повітряні тривоги в Україні в реальному часі.

## Конфігурація

### 1. Додайте налаштування у `config/services.php`:

```php
'air_alert' => [
    'token' => env('AIR_ALERT_API_TOKEN'),
],
```

### 2. Додайте у `.env`:

```env
AIR_ALERT_API_TOKEN=8a0343...........4ab2203
```

## API Methods

### 1. getActiveAlerts() - Отримати всі активні тривоги

```php
use App\Services\AirAlertService;

$airAlert = new AirAlertService();
$alerts = $airAlert->getActiveAlerts();

// Результат:
// [
//     'alerts' => [
//         '1' => [
//             'region_id' => '1',
//             'region_name' => 'Вінницька область',
//             'alert' => true,
//             'alert_type' => 'air_raid',
//             'started_at' => '2025-11-11 10:00:00',
//         ],
//         ...
//     ]
// ]
```

### 2. getAlertByRegion() - Отримати статус тривоги для конкретного регіону

```php
$alert = $airAlert->getAlertByRegion('5'); // Київська область

// Результат:
// [
//     'region_id' => '5',
//     'region_name' => 'Київська область',
//     'alert' => true,
//     'alert_type' => 'air_raid',
//     'started_at' => '2025-11-11 10:00:00',
// ]
```

### 3. getRegions() - Отримати список всіх регіонів

```php
$regions = $airAlert->getRegions();

// Результат:
// [
//     '1' => 'Вінницька область',
//     '2' => 'Волинська область',
//     '3' => 'Дніпропетровська область',
//     ...
// ]
```

### 4. getAlertHistory() - Отримати історію тривог

```php
$history = $airAlert->getAlertHistory(
    regionId: '5',
    from: '2025-11-01',
    to: '2025-11-11'
);
```

### 5. isAlertActive() - Перевірити чи активна тривога

```php
if ($airAlert->isAlertActive('5')) {
    echo 'У Київській області тривога!';
}
```

## ID Регіонів України (UID з alerts.in.ua)

Згідно з офіційною документацією alerts.in.ua:

| UID | Регіон |
|-----|--------|
| 3 | Хмельницька область |
| 4 | Вінницька область |
| 5 | Рівненська область |
| 8 | Волинська область |
| 9 | Дніпропетровська область |
| 10 | Житомирська область |
| 11 | Закарпатська область |
| 12 | Запорізька область |
| 13 | Івано-Франківська область |
| 14 | Київська область |
| 15 | Кіровоградська область |
| 16 | Луганська область |
| 17 | Миколаївська область |
| 18 | Одеська область |
| 19 | Полтавська область |
| 20 | Сумська область |
| 21 | Тернопільська область |
| 22 | Харківська область |
| 23 | Херсонська область |
| 24 | Черкаська область |
| 25 | Чернігівська область |
| 26 | Чернівецька область |
| 27 | Львівська область |
| 28 | Донецька область |
| 29 | Автономна Республіка Крим |
| 30 | м. Севастополь |
| 31 | м. Київ |

## Artisan Command для моніторингу

### Моніторинг всіх регіонів

```bash
php artisan air-alert:monitor
```

### Моніторинг конкретного регіону

```bash
# Київ
php artisan air-alert:monitor --region=31

# Київська область
php artisan air-alert:monitor --region=14

# Харківська область
php artisan air-alert:monitor --region=22

# Львівська область
php artisan air-alert:monitor --region=27
```

### Щоденний звіт про тривоги

```bash
# Звіт по всій Україні
php artisan air-alert:daily-report

# Звіт для конкретного регіону
php artisan air-alert:daily-report --region=31  # Київ
php artisan air-alert:daily-report --region=22  # Харків
```

Звіт автоматично відправляється у Telegram з детальною інформацією:
- Кількість тривог
- Загальна тривалість
- Часові проміжки кожної тривоги
- Статистика по регіонах (якщо звіт загальний)

## Налаштування автоматичного моніторингу

### База даних для історії тривог

Команда `air-alert:monitor` автоматично зберігає історію всіх тривог у базу даних.

#### Запуск міграції

```bash
php artisan migrate
```

Це створить таблицю `air_alerts` з наступними полями:
- `region_id` - ID регіону
- `region_name` - Назва регіону
- `is_active` - Чи активна тривога зараз
- `alert_type` - Тип тривоги
- `started_at` - Час початку
- `ended_at` - Час закінчення
- `duration_minutes` - Тривалість у хвилинах

#### Робота з моделлю AirAlert

```php
use App\Models\AirAlert;

// Отримати всі активні тривоги
$activeAlerts = AirAlert::active()->get();

// Отримати історію для Києва
$kyivHistory = AirAlert::forRegion('25')
    ->orderBy('started_at', 'desc')
    ->limit(10)
    ->get();

// Статистика тривог за сьогодні
$todayAlerts = AirAlert::whereDate('started_at', today())
    ->count();

// Середня тривалість тривог за тиждень
$avgDuration = AirAlert::whereBetween('started_at', [now()->subWeek(), now()])
    ->whereNotNull('duration_minutes')
    ->avg('duration_minutes');
```

### Варіант 1: Cron (кожні 2 хвилини)

Додайте у `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('air-alert:monitor')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Щоденний звіт о 20:00
Schedule::command('air-alert:daily-report')
    ->dailyAt('20:00')
    ->runInBackground();
```

Або додайте у crontab:
```cron
*/2 * * * * cd /path-to-your-project && php artisan air-alert:monitor >> /dev/null 2>&1
```

### Варіант 2: Supervisor (постійний моніторинг)

Створіть файл `/etc/supervisor/conf.d/air-alert-monitor.conf`:

```ini
[program:air-alert-monitor]
command=php /path-to-your-project/artisan air-alert:monitor
directory=/path-to-your-project
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/air-alert-monitor.log
```

Перезапустіть Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start air-alert-monitor
```

## Інтеграція з Telegram

Команда автоматично відправляє повідомлення через `SendAirAlertNotification` Job:

```php
use App\Jobs\SendAirAlertNotification;

// Початок тривоги
SendAirAlertNotification::dispatch(
    region: 'Київська область',
    isActive: true,
    additionalInfo: 'Загроза застосування балістичних ракет'
);

// Відбій тривоги
SendAirAlertNotification::dispatch(
    region: 'Київська область',
    isActive: false
);
```

## Приклад повного циклу моніторингу

```php
<?php

namespace App\Console\Commands;

use App\Services\AirAlertService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CustomAirAlertMonitor extends Command
{
    protected $signature = 'custom:monitor-alerts {region}';
    protected $description = 'Моніторинг тривог для конкретного регіону';

    public function handle(
        AirAlertService $airAlert,
        TelegramService $telegram
    ): int {
        $regionId = $this->argument('region');
        $cacheKey = "alert_status_{$regionId}";
        
        // Отримуємо поточний статус
        $alert = $airAlert->getAlertByRegion($regionId);
        
        if (!$alert) {
            $this->error('Не вдалося отримати дані');
            return Command::FAILURE;
        }
        
        $previousStatus = Cache::get($cacheKey, false);
        $currentStatus = $alert['alert'] ?? false;
        
        // Якщо статус змінився
        if ($previousStatus !== $currentStatus) {
            $regionName = $alert['region_name'] ?? $regionId;
            
            if ($currentStatus) {
                $message = "🚨 <b>ПОВІТРЯНА ТРИВОГА!</b>\n\n";
                $message .= "📍 Регіон: <b>{$regionName}</b>\n";
                $message .= "⚠️ <i>Пройдіть до укриття!</i>";
                
                $telegram->sendMessage($message, sendToDev: true);
                $this->warn("🚨 Тривога у {$regionName}");
            } else {
                $message = "✅ <b>Відбій тривоги</b>\n\n";
                $message .= "📍 Регіон: <b>{$regionName}</b>";
                
                $telegram->sendMessage($message, sendToDev: true);
                $this->info("✅ Відбій у {$regionName}");
            }
            
            Cache::put($cacheKey, $currentStatus, now()->addDay());
        }
        
        return Command::SUCCESS;
    }
}
```

## Тестування

### 1. Перевірка підключення до API

```bash
php artisan tinker
```

```php
$airAlert = new \App\Services\AirAlertService();

// Отримати список регіонів
$regions = $airAlert->getRegions();
dd($regions);

// Отримати активні тривоги
$alerts = $airAlert->getActiveAlerts();
dd($alerts);

// Перевірити конкретний регіон (Київ)
$alert = $airAlert->getAlertByRegion('25');
dd($alert);
```

### 2. Тестування команди моніторингу

```bash
# Запустити одноразово
php artisan air-alert:monitor

# Запустити для конкретного регіону
php artisan air-alert:monitor --region=25
```

## Логування

Сервіс автоматично логує всі операції:

```
[2025-11-11 10:30:45] INFO: Air alert status changed {"region":"Київська область","status":"active"}
[2025-11-11 10:35:00] INFO: Air alert status changed {"region":"Київська область","status":"clear"}
[2025-11-11 10:40:15] ERROR: Failed to get active alerts {"status":401,"response":"..."}
```

## Обробка помилок

Всі методи сервісу повертають `null` у випадку помилки та логують деталі:

```php
$alerts = $airAlert->getActiveAlerts();

if (!$alerts) {
    // Помилка запиту або відсутня конфігурація
    Log::error('Не вдалося отримати дані про тривоги');
}
```

## Розширені можливості

### Створення статистики тривог

```php
// app/Console/Commands/AirAlertStatistics.php
public function handle(AirAlertService $airAlert): int
{
    $history = $airAlert->getAlertHistory(
        regionId: '25',
        from: now()->subMonth()->toDateString(),
        to: now()->toDateString()
    );
    
    // Аналіз статистики
    $totalAlerts = count($history['alerts'] ?? []);
    $this->info("За місяць було {$totalAlerts} тривог");
    
    return Command::SUCCESS;
}
```

### Webhook для реал-тайм сповіщень

```php
// routes/api.php
Route::post('/webhooks/air-alert', function (Request $request) {
    $data = $request->all();
    
    SendAirAlertNotification::dispatch(
        region: $data['region_name'],
        isActive: $data['alert'],
        additionalInfo: $data['alert_type']
    );
    
    return response()->json(['status' => 'ok']);
});
```

## Безпека

- ✅ Ніколи не commitте токени в git
- ✅ Використовуйте `.env` для зберігання токенів
- ✅ Обмежте доступ до API тільки необхідними IP
- ✅ Регулярно перевіряйте логи на підозрілу активність

## Корисні посилання

- 📚 [Документація API](https://devs.alerts.in.ua/)
- 📊 [Таблиця ID регіонів](https://docs.google.com/spreadsheets/d/1XnTOzcPHd1LZUrarR1Fk43FUyl8Ae6a6M7pcwDRjNdA/edit?gid=0#gid=0)
- 💬 Telegram: Для сповіщень використовується `TelegramService` (див. `telegram-service-guide.md`)

