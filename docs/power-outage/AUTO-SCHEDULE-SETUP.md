# Налаштування автоматичного отримання графіка відключень електроенергії

## Огляд

Команда `power:fetch-schedule` налаштована для автоматичного запуску кожні 10 хвилин для перевірки та отримання актуального графіка відключень електроенергії.

## Конфігурація планувальника

### routes/console.php

```php
// Проверка графика отключений электроэнергии каждые 10 минут
Schedule::command('power:fetch-schedule')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
```

### Пояснення параметрів:

- **`everyTenMinutes()`** - команда виконується кожні 10 хвилин
- **`withoutOverlapping()`** - запобігає паралельному виконанню команди
- **`onOneServer()`** - гарантує виконання тільки на одному сервері (важливо для multi-server setup)
- **`runInBackground()`** - команда виконується у фоновому режимі

## Налаштування на сервері

### 1. Додайте Laravel Scheduler до Cron

На вашому сервері потрібно налаштувати **один запис в cron**, який буде запускати Laravel Scheduler кожну хвилину:

```bash
crontab -e
```

Додайте наступний рядок (замініть `/path/to/your/project` на реальний шлях до вашого проєкту):

```cron
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

**Приклад для конкретного користувача:**

```cron
* * * * * cd /home/igor/developer/task.famhub.local && php8.4 artisan schedule:run >> /dev/null 2>&1
```

### 2. Збереження логів (опціонально)

Якщо ви хочете зберігати логи виконання scheduler:

```cron
* * * * * cd /path/to/your/project && php artisan schedule:run >> /path/to/your/project/storage/logs/scheduler.log 2>&1
```

### Перевірка налаштування

Після додавання cron запису, перевірте, що він працює:

```bash
# Автоматична комплексна перевірка (рекомендовано)
./scripts/check-power-schedule.sh

# Або вручну:

# Перевірити список заплановиних задач
php artisan schedule:list

# Запустити scheduler вручну для тесту
php artisan schedule:run

# Запустити команду безпосередньо
php artisan power:fetch-schedule
```

## Як це працює

1. **Cron** запускає `php artisan schedule:run` кожну хвилину
2. **Laravel Scheduler** перевіряє, які команди потрібно запустити
3. Кожні 10 хвилин виконується `power:fetch-schedule`:
   - Завантажується HTML з сайту ДТЕК
   - Парситься таблиця з графіком
   - Перевіряється, чи змінився графік
   - Якщо є зміни - зберігається в БД, генерується зображення, відправляються сповіщення
   - Якщо змін немає - нічого не відбувається

## Моніторинг

### Перевірка логів

```bash
# Логи Laravel
tail -f storage/logs/laravel.log

# Логи scheduler (якщо налаштовані)
tail -f storage/logs/scheduler.log
```

### Перевірка останнього запуску

```bash
# Показати останні записи в БД
php artisan tinker
>>> App\Models\PowerOutageSchedule::latest()->first();
>>> App\Models\PowerOutageSchedule::latest()->first()->fetched_at;
```

## Альтернативні інтервали

Якщо потрібно змінити інтервал перевірки, відредагуйте `routes/console.php`:

```php
// Кожні 5 хвилин
Schedule::command('power:fetch-schedule')->everyFiveMinutes();

// Кожні 15 хвилин
Schedule::command('power:fetch-schedule')->everyFifteenMinutes();

// Кожну годину
Schedule::command('power:fetch-schedule')->hourly();

// Кожні 30 хвилин
Schedule::command('power:fetch-schedule')->everyThirtyMinutes();

// Щодня о 08:00
Schedule::command('power:fetch-schedule')->dailyAt('08:00');
```

## Виробниче середовище (Production)

### Supervisor (рекомендовано)

Для більш надійного виконання рекомендується використовувати **Supervisor**:

**1. Встановіть Supervisor:**

```bash
sudo apt-get install supervisor
```

**2. Створіть конфігураційний файл:**

```bash
sudo nano /etc/supervisor/conf.d/laravel-scheduler.conf
```

**3. Додайте конфігурацію:**

```ini
[program:laravel-scheduler]
process_name=%(program_name)s
command=/bin/bash -c "while true; do php /path/to/your/project/artisan schedule:run --verbose --no-interaction & sleep 60; done"
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/scheduler.log
stopwaitsecs=3600
```

**4. Оновіть Supervisor:**

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-scheduler
```

### Systemd Timer (альтернатива)

**1. Створіть service файл:**

```bash
sudo nano /etc/systemd/system/laravel-scheduler.service
```

```ini
[Unit]
Description=Laravel Scheduler
After=network.target

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/path/to/your/project
ExecStart=/usr/bin/php artisan schedule:run

[Install]
WantedBy=multi-user.target
```

**2. Створіть timer файл:**

```bash
sudo nano /etc/systemd/system/laravel-scheduler.timer
```

```ini
[Unit]
Description=Run Laravel Scheduler every minute
Requires=laravel-scheduler.service

[Timer]
OnBootSec=1min
OnUnitActiveSec=1min
Unit=laravel-scheduler.service

[Install]
WantedBy=timers.target
```

**3. Активуйте timer:**

```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-scheduler.timer
sudo systemctl start laravel-scheduler.timer
sudo systemctl status laravel-scheduler.timer
```

## Troubleshooting

### Команда не виконується

1. Перевірте, чи працює cron:
   ```bash
   sudo service cron status
   ```

2. Перевірте логи cron:
   ```bash
   grep CRON /var/log/syslog
   ```

3. Перевірте права доступу:
   ```bash
   ls -la /path/to/your/project/storage
   chmod -R 775 storage
   chown -R www-data:www-data storage
   ```

### Команда виконується, але не працює

1. Запустіть вручну з verbose:
   ```bash
   php artisan power:fetch-schedule -v
   ```

2. Перевірте логи Laravel:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Перевірте, чи встановлено Imagick:
   ```bash
   php -m | grep imagick
   ```

## Див. також

- [QUICKSTART.md](QUICKSTART.md) - Швидкий старт
- [COMMANDS.md](COMMANDS.md) - Опис команд
- [HOW-NOTIFICATIONS-WORK.md](HOW-NOTIFICATIONS-WORK.md) - Як працюють сповіщення
- [TELEGRAM-GUIDE.md](TELEGRAM-GUIDE.md) - Налаштування Telegram

