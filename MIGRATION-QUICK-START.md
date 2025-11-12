# 🚀 Швидка міграція до mi-razom

## Крок 1: Міграція файлів

```bash
cd /home/igor/developer/task.famhub.local
./scripts/migrate-to-mi-razom.sh
```

## Крок 2: Перевірка міграції

```bash
./scripts/check-migration.sh
```

Скрипт автоматично перевірить:
- ✅ Всі файли скопійовано
- ✅ Конфігурація налаштована
- ✅ Системні пакети встановлені
- ✅ Composer залежності є

## Що буде скопійовано

✅ **25 файлів:**
- 2 Models (AirAlert, PowerOutageSchedule)
- 4 Services (AirAlert, Telegram, PowerOutageImageGenerator, PowerOutageParser)
- 2 Jobs (повідомлення про тривоги та відключення)
- 7 Console Commands (моніторинг, звіти, тести)
- 3 Migrations (таблиці бази даних)
- 1 Factory (для тестів)
- 1 Test (PowerOutageSchedule)
- 4 Documentation files (гайди)
- 1 Directory (power-outage-images)

## Після запуску скрипту

```bash
cd /home/igor/developer/mi-razom

# 1. Додайте до .env
TELEGRAM_BOT_TOKEN=your_token
TELEGRAM_CHAT_ID=your_chat_id
AIR_ALERT_API_TOKEN=your_token

# 2. Додайте до config/services.php
# (скрипт міграції покаже що саме)

# 3. Встановіть системні пакети
sudo apt-get install -y imagemagick php-imagick fonts-dejavu fonts-dejavu-core

# 4. Встановіть Composer пакети
composer require guzzlehttp/guzzle intervention/image

# 5. Виконайте міграції
php artisan migrate

# 6. Тест
php artisan telegram:test-alert --alert
```

## 📚 Детальна документація

Див. `docs/MIGRATION-TO-MI-RAZOM.md`

