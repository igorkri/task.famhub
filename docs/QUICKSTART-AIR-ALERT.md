# 🚀 Швидкий старт - Air Alert Integration

## 📦 Встановлення

### 1. Налаштування змінних середовища

```bash
# Скопіюйте .env.example до .env (якщо ще не зробили)
cp .env.example .env
```

Додайте до `.env`:

```env
# Telegram Bot (отримайте через @BotFather)
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=-1001234567890

# Air Alert API (наданий токен)
AIR_ALERT_API_TOKEN=8a0343dfa946b66b0b4c7b6e6c1f867076ea1a74ab2203
```

### 2. Запуск міграцій

```bash
php artisan migrate
```

Це створить таблицю `air_alerts` для збереження історії тривог.

### 3. Перевірка функціоналу

#### Тест Telegram Bot

```bash
php artisan tinker
```

```php
$telegram = new \App\Services\TelegramService();
$telegram->sendMessage('🧪 <b>Тест</b> Telegram Bot працює!');
exit
```

#### Тест Air Alert API

```bash
php artisan tinker
```

```php
$airAlert = new \App\Services\AirAlertService();

// Отримати статус всіх регіонів
$alerts = $airAlert->getActiveAlerts();
print_r($alerts);

```php
$kyiv = $airAlert->getAlertByRegion('31');  # м. Київ
print_r($kyiv);

exit
```

#### Тест моніторингу

```bash
# Запустити моніторинг для Києва
php artisan air-alert:monitor --region=31

# Згенерувати щоденний звіт
php artisan air-alert:daily-report --region=31
```

## ⚙️ Налаштування автоматичного моніторингу

### Варіант 1: Laravel Scheduler (Рекомендовано)

Відкрийте `routes/console.php` та додайте:

```php
use Illuminate\Support\Facades\Schedule;

// Моніторинг тривог кожні 2 хвилини
Schedule::command('air-alert:monitor')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Щоденний звіт о 20:00
Schedule::command('air-alert:daily-report')
    ->dailyAt('20:00')
    ->runInBackground();
```

Додайте до crontab:

```bash
crontab -e
```

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Варіант 2: Supervisor (для продакшну)

Створіть файл `/etc/supervisor/conf.d/air-alert-monitor.conf`:

```ini
[program:air-alert-monitor]
process_name=%(program_name)s
command=php /path-to-project/artisan air-alert:monitor
directory=/path-to-project
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/air-alert-monitor.log
stopwaitsecs=60
```

Перезапустіть Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start air-alert-monitor
```

## 📱 Налаштування Telegram Bot

### 1. Створення бота

1. Відкрийте [@BotFather](https://t.me/BotFather) у Telegram
2. Надішліть `/newbot`
3. Вкажіть назву та username бота
4. Скопіюйте токен бота → `TELEGRAM_BOT_TOKEN`

### 2. Отримання Chat ID

#### Для приватного чату:

1. Напишіть боту будь-яке повідомлення
2. Перейдіть: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
3. Знайдіть `"chat":{"id":123456789}`
4. Скопіюйте ID → `TELEGRAM_CHAT_ID`

#### Для каналу/групи:

1. Додайте бота до каналу як адміністратора
2. Напишіть у канал будь-яке повідомлення
3. Перейдіть: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
4. Знайдіть `"chat":{"id":-1001234567890}`
5. Скопіюйте ID з мінусом → `TELEGRAM_CHAT_ID=-1001234567890`

## 🧪 Тестові сценарії

### Сценарій 1: Відправка повідомлення

```php
use App\Services\TelegramService;

$telegram = app(TelegramService::class);

// Просте повідомлення
$telegram->sendMessage('Привіт! 👋');

// З HTML форматуванням
$telegram->sendMessage(
    '<b>Важливо!</b> Це <i>тестове</i> повідомлення',
    sendToDev: true
);
```

### Сценарій 2: Перевірка тривоги

```php
use App\Services\AirAlertService;
use App\Services\TelegramService;

$airAlert = app(AirAlertService::class);
$telegram = app(TelegramService::class);

$alert = $airAlert->getAlertByRegion('31'); // м. Київ

if ($alert && $alert['alert']) {
    $message = "🚨 <b>ТРИВОГА!</b>\n";
    $message .= "📍 {$alert['region_name']}";
    $telegram->sendMessage($message);
} else {
    $telegram->sendMessage("✅ Тривоги немає");
}
```

### Сценарій 3: Відправка через Job

```php
use App\Jobs\SendAirAlertNotification;

// Початок тривоги
SendAirAlertNotification::dispatch(
    region: 'Київська область',
    isActive: true,
    additionalInfo: 'Загроза застосування балістичних ракет'
);

// Відбій
SendAirAlertNotification::dispatch(
    region: 'Київська область',
    isActive: false
);
```

### Сценарій 4: Робота з історією

```php
use App\Models\AirAlert;

// Останні 10 тривог
$recent = AirAlert::latest()->limit(10)->get();

// Тривоги за сьогодні
$today = AirAlert::whereDate('started_at', today())->get();

// Історія для Києва
$kyivHistory = AirAlert::forRegion('31')->latest()->limit(5)->get();

// Статистика
$stats = [
    'total' => AirAlert::count(),
    'today' => AirAlert::whereDate('started_at', today())->count(),
    'active' => AirAlert::active()->count(),
    'avg_duration' => AirAlert::avg('duration_minutes'),
];

print_r($stats);
```

## 🔍 Моніторинг та логи

### Перегляд логів

```bash
# Real-time моніторинг
tail -f storage/logs/laravel.log

# Тільки помилки
tail -f storage/logs/laravel.log | grep ERROR

# Тільки Air Alert
tail -f storage/logs/laravel.log | grep "Air alert"
```

### Логи Supervisor

```bash
tail -f /var/log/air-alert-monitor.log
```

## 🐛 Усунення неполадок

### Telegram Bot не відправляє повідомлення

```bash
# Перевірте конфігурацію
php artisan tinker
config('services.telegram.bot_token')
config('services.telegram.chat_id')
exit
```

### Air Alert API не працює

```bash
# Перевірте токен
php artisan tinker
config('services.air_alert.token')

# Тест API
$airAlert = new \App\Services\AirAlertService();
$alerts = $airAlert->getActiveAlerts();
dd($alerts);
exit
```

### Scheduler не запускається

```bash
# Перевірте чи працює cron
crontab -l

# Запустіть вручну
php artisan schedule:run

# Перевірте список запланованих завдань
php artisan schedule:list
```

## 📊 Корисні команди

```bash
# Список всіх Artisan команд
php artisan list

# Допомога по команді
php artisan air-alert:monitor --help

# Очистити кеш
php artisan cache:clear

# Очистити черги
php artisan queue:clear

# Перезапустити черги
php artisan queue:restart
```

## 🎯 Наступні кроки

1. ✅ Налаштуйте `.env`
2. ✅ Запустіть міграції
3. ✅ Протестуйте Telegram Bot
4. ✅ Протестуйте Air Alert API
5. ✅ Налаштуйте автоматичний моніторинг
6. ✅ Перевірте логи
7. 🚀 Насолоджуйтесь автоматичними сповіщеннями!

## 📚 Додаткова документація

- [TelegramService Guide](telegram-service-guide.md)
- [Air Alert Service Guide](air-alert-service-guide.md)
- [Integration Overview](air-alert-telegram-integration.md)

---

**Слава Україні! 🇺🇦**

