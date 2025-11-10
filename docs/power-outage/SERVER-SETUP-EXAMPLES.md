# Приклади налаштування для різних серверів

## Ubuntu / Debian

### Cron (найпростіше)

```bash
# Відкрити crontab
crontab -e

# Додати рядок (для користувача www-data)
* * * * * cd /var/www/html/task.famhub.local && php artisan schedule:run >> /dev/null 2>&1

# Або для користувача igor
* * * * * cd /home/igor/developer/task.famhub.local && php8.4 artisan schedule:run >> /dev/null 2>&1

# З логами
* * * * * cd /home/igor/developer/task.famhub.local && php8.4 artisan schedule:run >> /home/igor/developer/task.famhub.local/storage/logs/scheduler.log 2>&1
```

### Supervisor (рекомендовано для production)

```bash
# Встановити Supervisor
sudo apt-get update
sudo apt-get install supervisor

# Створити конфігурацію
sudo nano /etc/supervisor/conf.d/laravel-scheduler.conf
```

**Конфігурація:**

```ini
[program:laravel-scheduler]
process_name=%(program_name)s
command=/bin/bash -c "while true; do php /home/igor/developer/task.famhub.local/artisan schedule:run --verbose --no-interaction & sleep 60; done"
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/home/igor/developer/task.famhub.local/storage/logs/scheduler.log
stopwaitsecs=3600
```

**Активувати:**

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-scheduler
sudo supervisorctl status
```

### Systemd Timer

**Файл сервісу:** `/etc/systemd/system/laravel-scheduler.service`

```ini
[Unit]
Description=Laravel Scheduler
After=network.target

[Service]
Type=oneshot
User=www-data
Group=www-data
WorkingDirectory=/home/igor/developer/task.famhub.local
ExecStart=/usr/bin/php8.4 artisan schedule:run
StandardOutput=append:/home/igor/developer/task.famhub.local/storage/logs/scheduler.log
StandardError=append:/home/igor/developer/task.famhub.local/storage/logs/scheduler.log

[Install]
WantedBy=multi-user.target
```

**Файл таймера:** `/etc/systemd/system/laravel-scheduler.timer`

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

**Активувати:**

```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-scheduler.timer
sudo systemctl start laravel-scheduler.timer
sudo systemctl status laravel-scheduler.timer

# Переглянути логи
journalctl -u laravel-scheduler.service -f
```

---

## CentOS / RHEL / AlmaLinux

### Cron

```bash
# Для користувача www-data / nginx
sudo -u www-data crontab -e

# Додати рядок
* * * * * cd /var/www/html/task.famhub.local && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### Supervisor

```bash
# Встановити
sudo yum install supervisor
sudo systemctl enable supervisord
sudo systemctl start supervisord

# Конфігурація аналогічна Ubuntu
sudo vi /etc/supervisord.d/laravel-scheduler.ini
```

---

## Docker

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build: .
    volumes:
      - .:/var/www/html
    
  scheduler:
    build: .
    command: php artisan schedule:work
    volumes:
      - .:/var/www/html
    depends_on:
      - app
```

### Альтернатива (з cron у контейнері)

**Dockerfile:**

```dockerfile
FROM php:8.4-fpm

# ... інші інструкції ...

# Встановити cron
RUN apt-get update && apt-get install -y cron

# Копіювати crontab
COPY docker/crontab /etc/cron.d/laravel-scheduler

# Надати права
RUN chmod 0644 /etc/cron.d/laravel-scheduler
RUN crontab /etc/cron.d/laravel-scheduler

# Запустити cron
CMD cron && php-fpm
```

**docker/crontab:**

```
* * * * * cd /var/www/html && php artisan schedule:run >> /var/www/html/storage/logs/scheduler.log 2>&1
```

---

## Laravel Forge

**Scheduler вже налаштовано автоматично!**

Forge автоматично додає cron job при створенні сайту.

Перевірити можна в розділі "Scheduler" вашого сайту.

---

## Laravel Vapor (AWS Lambda)

**Scheduler працює автоматично через CloudWatch Events**

Налаштування в `vapor.yml`:

```yaml
id: 12345
name: task-famhub
environments:
  production:
    # ... інші налаштування ...
    
    # Scheduler працює автоматично
    scheduler: true
```

---

## Shared Hosting (cPanel, Plesk)

### cPanel

1. Увійдіть в cPanel
2. Знайдіть "Cron Jobs"
3. Додайте новий cron job:
   - **Minute:** `*`
   - **Hour:** `*`
   - **Day:** `*`
   - **Month:** `*`
   - **Weekday:** `*`
   - **Command:** 
     ```bash
     cd /home/username/public_html && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
     ```

### Plesk

1. Увійдіть в Plesk
2. Перейдіть до "Scheduled Tasks"
3. Створіть новий task:
   - **Run:** Custom Script
   - **Schedule:** Every minute (*/1 * * * *)
   - **Command:**
     ```bash
     cd /var/www/vhosts/domain.com/httpdocs && php artisan schedule:run
     ```

---

## Windows Server

### Task Scheduler

1. Відкрийте "Task Scheduler"
2. Створіть нову задачу:
   - **Name:** Laravel Scheduler
   - **Trigger:** Repeat every 1 minute
   - **Action:** Start a program
     - **Program:** `C:\php\php.exe`
     - **Arguments:** `artisan schedule:run`
     - **Start in:** `C:\inetpub\wwwroot\task.famhub.local`

### PowerShell скрипт

**run-scheduler.ps1:**

```powershell
while ($true) {
    Set-Location "C:\inetpub\wwwroot\task.famhub.local"
    & "C:\php\php.exe" artisan schedule:run
    Start-Sleep -Seconds 60
}
```

**Запустити як службу:**

```powershell
# Створити службу
New-Service -Name "LaravelScheduler" `
    -BinaryPathName "powershell.exe -File C:\path\to\run-scheduler.ps1" `
    -StartupType Automatic

# Запустити службу
Start-Service LaravelScheduler
```

---

## Перевірка налаштування (для всіх платформ)

### 1. Перевірити список задач

```bash
php artisan schedule:list
```

### 2. Запустити вручну

```bash
php artisan schedule:run -v
```

### 3. Перевірити конкретну команду

```bash
php artisan power:fetch-schedule -v
```

### 4. Моніторинг логів

```bash
# Linux/Mac
tail -f storage/logs/laravel.log | grep -i schedule

# Windows PowerShell
Get-Content storage/logs/laravel.log -Wait | Select-String "schedule"
```

---

## Troubleshooting

### Права доступу (Linux)

```bash
# Дати права на папку storage
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Або для вашого користувача
sudo chown -R igor:igor storage
sudo chmod -R 775 storage
```

### Логування

Додайте в `routes/console.php`:

```php
Schedule::command('power:fetch-schedule')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/power-schedule.log'));
```

### Перевірка PHP версії

```bash
# Linux
which php
php -v

# Якщо потрібна інша версія
which php8.4
/usr/bin/php8.4 artisan schedule:run
```

---

## Рекомендації

| Середовище | Рекомендований метод |
|------------|---------------------|
| Development | Cron |
| Shared Hosting | cPanel/Plesk Cron |
| VPS/Dedicated | Supervisor |
| Docker | schedule:work або cron в контейнері |
| Laravel Forge | Автоматично |
| Laravel Vapor | Автоматично |
| Windows Server | Task Scheduler або PowerShell служба |

