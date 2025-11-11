# 🏙️ Моніторинг Полтавської області

## ⚠️ Важливо!

**IoT endpoint працює тільки для областей!** Детальні UID громад (1042-1065) не підтримуються IoT API. 

Для моніторингу громад використовується основний endpoint `/v1/alerts/active.json`, який повертає всі активні тривоги з деталями.

## 📍 Доступні рівні моніторингу

### Області (працює IoT endpoint)

| UID | Назва | API Endpoint |
|-----|-------|--------------|
| **19** | Полтавська область | ✅ IoT endpoint |

### Громади (тільки через active alerts)

Детальна інформація про тривоги в громадах Полтавської області доступна **тільки** через `/v1/alerts/active.json` з фільтром по області.

API повертає дані з полями:
- `location_title` - назва громади
- `location_type` - тип (oblast, raion, hromada, city)
- `alert_type` - тип тривоги (air_raid, artillery_shelling, urban_fights)
- `started_at` - час початку

## 🚀 Artisan команди

### 1. Моніторинг Полтавської області (за замовчуванням)

```bash
php artisan air-alert:monitor-poltava
```

Перевіряє статус тривоги для всієї Полтавської області через IoT endpoint (швидко).

### 2. Детальний моніторинг з громадами

```bash
php artisan air-alert:monitor-poltava --all
```

Показує всі активні тривоги в Полтавській області з деталями по громадах:
- Назва громади/району
- Тип локації (область/район/громада/місто)
- Тип тривоги (повітряна/артобстріл/міські бої)
- Час початку

### 3. Загальна команда для області

```bash
# Полтавська область
php artisan air-alert:monitor --region=19
```

## 💻 Програмний код

### Базове використання

```php
use App\Services\AirAlertService;

$airAlert = new AirAlertService();

// Перевірити Полтавську область (IoT endpoint - швидко)
$poltava = $airAlert->getAlertByRegion('19');

if ($poltava && $poltava['alert']) {
    echo "🚨 Тривога у Полтавській області!\n";
}
```

### Моніторинг громад (детально)

```php
use App\Services\AirAlertService;

$airAlert = new AirAlertService();

// Отримати всі активні тривоги в Полтавській області
$poltavaAlerts = $airAlert->getActiveAlertsForOblast('Полтавська область');

if (empty($poltavaAlerts)) {
    echo "✅ Тривог у Полтавській області немає\n";
} else {
    echo "🚨 Активних тривог: " . count($poltavaAlerts) . "\n\n";
    
    foreach ($poltavaAlerts as $alert) {
        echo "📍 {$alert['location_title']}\n";
        echo "   Тип: {$alert['alert_type']}\n";
        echo "   Почалась: {$alert['started_at']}\n\n";
    }
}
```

### Відправка сповіщень через Job

```php
use App\Jobs\SendAirAlertNotification;

// Тривога у місті
SendAirAlertNotification::dispatch(
    region: 'м. Полтава та Полтавська територіальна громада',
    isActive: true,
    additionalInfo: 'Увімкнено сирени'
);

// Відбій у районі
SendAirAlertNotification::dispatch(
    region: 'Полтавський район',
    isActive: false
);
```

### Статистика по Полтавському регіону

```php
use App\Models\AirAlert;

// Тривоги у м. Полтава за сьогодні
$poltavaToday = AirAlert::forRegion('1060')
    ->whereDate('started_at', today())
    ->get();

echo "Тривог у м. Полтава сьогодні: {$poltavaToday->count()}\n";

// Тривоги у всіх громадах за тиждень
$poltavaHromadas = [
    '1042', '1043', '1044', '1045', '1046', '1047', '1048', '1049',
    '1050', '1051', '1052', '1053', '1054', '1055', '1056', '1057',
    '1058', '1059', '1060', '1061', '1062', '1063', '1064', '1065'
];

$weekAlerts = AirAlert::whereIn('region_id', $poltavaHromadas)
    ->whereBetween('started_at', [now()->subWeek(), now()])
    ->get();

echo "Тривог у Полтавському районі за тиждень: {$weekAlerts->count()}\n";

// Середня тривалість тривог
$avgDuration = AirAlert::whereIn('region_id', $poltavaHromadas)
    ->whereNotNull('duration_minutes')
    ->avg('duration_minutes');

echo "Середня тривалість: " . round($avgDuration) . " хв\n";
```

## ⏰ Автоматизація

### Laravel Scheduler

Додайте у `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

// Моніторинг міста та району кожні 2 хвилини
Schedule::command('air-alert:monitor-poltava')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Моніторинг всіх громад кожні 5 хвилин
Schedule::command('air-alert:monitor-poltava --all')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
```

### Supervisor

Створіть `/etc/supervisor/conf.d/poltava-monitor.conf`:

```ini
[program:poltava-air-alert]
command=php /path-to-project/artisan air-alert:monitor-poltava
directory=/path-to-project
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/poltava-air-alert.log
stopwaitsecs=60
```

## 📊 Приклади виводу

### Моніторинг області (швидко)

```bash
$ php artisan air-alert:monitor-poltava

🔍 Моніторинг Полтавської області...
📍 Моніторинг: Полтавська область
ℹ️ Полтавська область: тривоги немає
```

### Детальний моніторинг з громадами

```bash
$ php artisan air-alert:monitor-poltava --all

🔍 Моніторинг Полтавської області...
📍 Моніторинг: громади Полтавської області
🚨 Знайдено активних тривог: 2

🏘️ Диканьська територіальна громада
   Тип: Повітряна тривога
   Почалась: 5 хвилин тому

🏙️ м. Полтава
   Тип: Повітряна тривога
   Почалась: 10 хвилин тому
```

або якщо тривог немає:

```bash
$ php artisan air-alert:monitor-poltava --all

🔍 Моніторинг Полтавської області...
📍 Моніторинг: громади Полтавської області
✅ Тривог у Полтавській області немає
```

## 🎯 Випадки використання

### 1. Швидка перевірка області

```bash
php artisan air-alert:monitor-poltava
```

Використовує IoT endpoint - найшвидший спосіб перевірити чи є тривога в області.

### 2. Детальна інформація про тривоги

```bash
php artisan air-alert:monitor-poltava --all
```

Показує конкретні громади/міста де активна тривога, тип тривоги та час початку.

### 3. API endpoint для отримання статусу

```php
Route::get('/api/poltava/alerts', function () {
    $airAlert = new \App\Services\AirAlertService();
    
    // Швидка перевірка області
    $oblast = $airAlert->getAlertByRegion('19');
    
    // Детальна інформація
    $details = $airAlert->getActiveAlertsForOblast('Полтавська область');
    
    return response()->json([
        'oblast_status' => $oblast,
        'active_alerts' => $details,
        'count' => count($details ?? []),
    ]);
});
```

### 4. Telegram бот з детальною інформацією

```php
use App\Services\AirAlertService;
use App\Services\TelegramService;

$airAlert = new AirAlertService();
$telegram = new TelegramService();

// Швидка перевірка
$poltava = $airAlert->getAlertByRegion('19');

if ($poltava && $poltava['alert']) {
    // Отримати деталі
    $alerts = $airAlert->getActiveAlertsForOblast('Полтавська область');
    
    $message = "🚨 <b>ТРИВОГА у Полтавській області!</b>\n\n";
    $message .= "Активних тривог: " . count($alerts) . "\n\n";
    
    foreach ($alerts as $alert) {
        $message .= "📍 {$alert['location_title']}\n";
        
        if ($alert['alert_type'] === 'artillery_shelling') {
            $message .= "⚠️ Артилерійський обстріл\n";
        } else {
            $message .= "🚨 Повітряна тривога\n";
        }
        
        $message .= "\n";
    }
    
    $telegram->sendMessage($message);
} else {
    $telegram->sendMessage("✅ Тривог у Полтавській області немає");
}
```

## 🔗 Пов'язана документація

- [AIR-ALERT-UID-UPDATE.md](AIR-ALERT-UID-UPDATE.md) - Повна таблиця UID
- [air-alert-service-guide.md](air-alert-service-guide.md) - Загальна документація
- [QUICKSTART-AIR-ALERT.md](QUICKSTART-AIR-ALERT.md) - Швидкий старт

---

**Слава Україні! 🇺🇦**

