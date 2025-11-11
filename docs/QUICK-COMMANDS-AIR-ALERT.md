# ⚡ Швидкі команди Air Alert System

## 🔧 Налаштування (один раз)

```bash
# 1. Додати у .env
TELEGRAM_BOT_TOKEN=ваш_токен_від_BotFather
TELEGRAM_CHAT_ID=ваш_chat_id
AIR_ALERT_API_TOKEN=8a0343dfa946b66b0b4c7b6e6c1f867076ea1a74ab2203

# 2. Запустити міграції
php artisan migrate

# 3. Тест з'єднання
php artisan tinker
$telegram = new \App\Services\TelegramService();
$telegram->sendMessage('🧪 Тест працює!');
exit
```

## 🚀 Команди для роботи

### Моніторинг тривог

```bash
# Всі регіони
php artisan air-alert:monitor

# Тільки Київ
php artisan air-alert:monitor --region=31

# Київська область
php artisan air-alert:monitor --region=14

# Харківська область
php artisan air-alert:monitor --region=22
```

### Щоденні звіти

```bash
# По всій Україні
php artisan air-alert:daily-report

# Конкретний регіон
php artisan air-alert:daily-report --region=31  # Київ
php artisan air-alert:daily-report --region=22  # Харків
```

### Перевірка API

```bash
php artisan tinker
```

```php
// Перевірити всі тривоги
$airAlert = new \App\Services\AirAlertService();
$alerts = $airAlert->getActiveAlerts();
print_r($alerts);

// Перевірити Київ
$kyiv = $airAlert->getAlertByRegion('31');
print_r($kyiv);

// Перевірити чи є тривога
if ($airAlert->isAlertActive('31')) {
    echo "Тривога у Києві!\n";
}

exit
```

### Робота з історією

```bash
php artisan tinker
```

```php
use App\Models\AirAlert;

// Всі тривоги за сьогодні
$today = AirAlert::whereDate('started_at', today())->get();
print_r($today->toArray());

// Активні тривоги
$active = AirAlert::active()->get();
echo "Активних тривог: " . $active->count() . "\n";

// Історія для Києва
$kyiv = AirAlert::forRegion('31')->latest()->limit(5)->get();
print_r($kyiv->toArray());

// Статистика
echo "Всього тривог: " . AirAlert::count() . "\n";
echo "Середня тривалість: " . round(AirAlert::avg('duration_minutes')) . " хв\n";

exit
```

## ⏰ Автоматичний запуск

### routes/console.php

```php
use Illuminate\Support\Facades\Schedule;

// Моніторинг кожні 2 хвилини
Schedule::command('air-alert:monitor')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Звіт щодня о 20:00
Schedule::command('air-alert:daily-report')
    ->dailyAt('20:00')
    ->runInBackground();
```

### Додати в crontab

```bash
crontab -e
```

```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 📊 Відправка з коду

### Через Job (рекомендовано)

```php
use App\Jobs\SendAirAlertNotification;

// Початок тривоги
SendAirAlertNotification::dispatch(
    region: 'Київська область',
    isActive: true,
    additionalInfo: 'Загроза застосування БР'
);

// Відбій
SendAirAlertNotification::dispatch(
    region: 'Київська область',
    isActive: false
);
```

### Напряму через сервіс

```php
use App\Services\TelegramService;

$telegram = app(TelegramService::class);

// Просте повідомлення
$telegram->sendMessage('🚨 Тривога!');

// З форматуванням
$telegram->sendMessage(
    '<b>🚨 ПОВІТРЯНА ТРИВОГА!</b>' . "\n" .
    '📍 Київ' . "\n" .
    '⚠️ <i>Пройдіть до укриття!</i>',
    sendToDev: true
);
```

## 🐛 Діагностика

### Перевірка конфігурації

```bash
php artisan tinker
```

```php
// Telegram
config('services.telegram.bot_token')
config('services.telegram.chat_id')

// Air Alert
config('services.air_alert.token')

exit
```

### Перегляд логів

```bash
# Всі логи
tail -f storage/logs/laravel.log

# Тільки Air Alert
tail -f storage/logs/laravel.log | grep -i "air alert"

# Тільки Telegram
tail -f storage/logs/laravel.log | grep -i "telegram"

# Тільки помилки
tail -f storage/logs/laravel.log | grep ERROR
```

### Очистка

```bash
# Очистити кеш
php artisan cache:clear

# Очистити логи
> storage/logs/laravel.log

# Перезапустити черги
php artisan queue:restart
```

## 🗺️ Найпопулярніші регіони

```bash
# Київ
--region=31

# Київська область
--region=14

# Львівська область
--region=27

# Харківська область
--region=22

# Дніпропетровська область
--region=9

# Одеська область
--region=18
```

## 📚 Документація

- `docs/QUICKSTART-AIR-ALERT.md` - Детальний швидкий старт
- `docs/telegram-service-guide.md` - Telegram API
- `docs/air-alert-service-guide.md` - Air Alert API
- `docs/air-alert-telegram-integration.md` - Загальний огляд
- `docs/AIR-ALERT-IMPLEMENTATION-SUMMARY.md` - Технічні деталі

## 🆘 Підтримка

При проблемах:
1. Перевірте `.env`
2. Перегляньте логи
3. Запустіть тести з `tinker`
4. Перевірте права доступу до файлів

---

**🇺🇦 Слава Україні!**

