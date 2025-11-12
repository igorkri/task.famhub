# Міграція функціоналу графіків, Telegram боту та Air Alert

## Дата: 2025-11-11

Цей документ описує всі файли, які потрібно перенести з `task.famhub.local` в `mi-razom` для роботи з графіками відключень світла, Telegram ботом та моніторингом повітряних тривог.

---

## 📋 Список файлів для міграції

### 1. Models (Моделі)

```
app/Models/AirAlert.php                    - Модель для повітряних тривог
app/Models/PowerOutageSchedule.php         - Модель для графіків відключень світла
```

### 2. Services (Сервіси)

```
app/Services/AirAlertService.php           - Сервіс для роботи з Air Alert API
app/Services/TelegramService.php           - Сервіс для відправки повідомлень в Telegram
app/Services/PowerOutageImageGenerator.php - Генератор зображень графіків відключень
app/Services/PowerOutageParserService.php  - Парсер HTML для отримання графіків
```

### 3. Jobs (Фонові задачі)

```
app/Jobs/SendAirAlertNotification.php      - Job для відправки сповіщень про тривоги
app/Jobs/SendPowerOutageNotification.php   - Job для відправки графіків відключень
```

### 4. Console Commands (Консольні команди)

```
app/Console/Commands/MonitorAirAlerts.php                  - Моніторинг усіх регіонів України
app/Console/Commands/AirAlertDailyReport.php               - Щоденний звіт по тривогам
app/Console/Commands/MonitorPoltavaRegion.php              - Моніторинг Полтавської області
app/Console/Commands/TestTelegramAlert.php                 - Тестування Telegram сповіщень
app/Console/Commands/SendTestTelegramMessage.php           - Відправка тестових повідомлень
app/Console/Commands/FetchPowerOutageSchedule.php          - Отримання графіків відключень
app/Console/Commands/SendPowerOutageNotificationCommand.php - Відправка сповіщень про відключення
```

### 5. Migrations (Міграції бази даних)

```
database/migrations/2025_11_11_122331_create_air_alerts_table.php        - Таблиця повітряних тривог
database/migrations/2025_11_09_142952_create_power_outage_schedules_table.php - Таблиця графіків
database/migrations/2025_11_10_110200_add_metadata_to_power_outage_schedules_table.php - Додаткові поля
```

### 6. Factories (Фабрики для тестів)

```
database/factories/PowerOutageScheduleFactory.php - Фабрика для тестових даних графіків
```

### 7. Tests (Тести)

```
tests/Feature/PowerOutageScheduleTest.php - Тести для графіків відключень
```

### 8. Directories (Директорії)

```
storage/app/power-outage-images/          - Папка для збереження згенерованих зображень
```

---

## ⚙️ Конфігурація

### 1. Додати до `config/services.php`:

```php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
],

'air_alert' => [
    'token' => env('AIR_ALERT_API_TOKEN'),
],
```

### 2. Додати до `.env`:

```env
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHAT_ID=your_chat_id_here

# Air Alert API Configuration
AIR_ALERT_API_TOKEN=your_air_alert_token_here
```

---

## 📦 Залежності

### Composer пакети:

```bash
composer require guzzlehttp/guzzle     # HTTP клієнт для API запитів
composer require intervention/image    # Генерація зображень графіків
```

Перевірте, чи встановлені ці пакети в `mi-razom`. Якщо ні - встановіть їх.

### Системні пакети:

Для генерації зображень графіків потрібні ImageMagick або GD та шрифти DejaVu:

```bash
# Для ImageMagick (рекомендовано)
sudo apt-get install -y imagemagick php-imagick fonts-dejavu fonts-dejavu-core

# АБО для GD (альтернатива)
sudo apt-get install -y php8.3-gd fonts-dejavu fonts-dejavu-core

# Перезапустіть PHP-FPM після встановлення
sudo systemctl restart php8.3-fpm
```

---

## 🚀 Автоматична міграція

### Використання скрипту:

```bash
cd /home/igor/developer/task.famhub.local
chmod +x scripts/migrate-to-mi-razom.sh
./scripts/migrate-to-mi-razom.sh
```

Скрипт автоматично:
- ✅ Створить необхідні директорії
- ✅ Скопіює всі файли
- ✅ Покаже інструкції для налаштування

---

## 📝 Кроки після міграції

### 1. Запуск міграцій

```bash
cd /home/igor/developer/mi-razom
php artisan migrate
```

### 2. Налаштування Cron

Додайте до crontab (`crontab -e`):

```cron
# Моніторинг Полтавської області (кожну хвилину)
* * * * * cd /home/igor/developer/mi-razom && php artisan monitor:poltava-region >> /dev/null 2>&1

# Щоденний звіт по тривогам (о 9:00)
0 9 * * * cd /home/igor/developer/mi-razom && php artisan air-alert:daily-report >> /dev/null 2>&1

# Отримання графіків відключень (кожні 5 хвилин)
*/5 * * * * cd /home/igor/developer/mi-razom && php artisan power-outage:fetch >> /dev/null 2>&1
```

### 3. Тестування

```bash
# Тест Telegram
php artisan telegram:test

# Тест Air Alert
php artisan telegram:test-alert --alert

# Тест графіків відключень
php artisan power-outage:fetch
php artisan power-outage:send-notification
```

---

## 🔍 Консольні команди

### Air Alert (Повітряні тривоги)

```bash
# Моніторинг Полтавської області
php artisan monitor:poltava-region

# Моніторинг усіх регіонів
php artisan monitor:air-alerts

# Щоденний звіт
php artisan air-alert:daily-report

# Тестове повідомлення про тривогу
php artisan telegram:test-alert --alert

# Тестове повідомлення про відбій
php artisan telegram:test-alert --clear
```

### Power Outage (Графіки відключень)

```bash
# Отримати графіки з сайту
php artisan power-outage:fetch

# Відправити сповіщення в Telegram
php artisan power-outage:send-notification
```

### Telegram

```bash
# Тест Telegram боту
php artisan telegram:test

# Відправити тестове повідомлення
php artisan send:test-telegram-message
```

---

## 📊 Що робить кожен компонент

### AirAlertService
- Інтеграція з API alerts.in.ua
- Отримання статусів повітряних тривог по регіонах
- Кешування даних для оптимізації

### TelegramService
- Відправка текстових повідомлень
- Відправка фото з підписом
- Форматування повідомлень

### PowerOutageImageGenerator
- Парсинг HTML таблиці з графіком
- Генерація PNG зображення з графіком
- Додавання легенди та назв груп
- Форматування за днями тижня

### PowerOutageParserService
- Отримання HTML з сайту ДТЕК
- Парсинг таблиці графіків
- Збереження даних в БД

---

## 🗂️ Структура бази даних

### Таблиця `air_alerts`

| Поле | Тип | Опис |
|------|-----|------|
| id | bigint | ID запису |
| region_uid | string | UID регіону |
| region_name | string | Назва регіону |
| status | enum | active/inactive |
| started_at | timestamp | Початок тривоги |
| ended_at | timestamp | Кінець тривоги |
| duration | integer | Тривалість у хвилинах |
| created_at | timestamp | Дата створення |
| updated_at | timestamp | Дата оновлення |

### Таблиця `power_outage_schedules`

| Поле | Тип | Опис |
|------|-----|------|
| id | bigint | ID запису |
| schedule_date | date | Дата графіка |
| group_number | string | Номер групи |
| raw_html | text | Вихідний HTML |
| metadata | json | Додаткові дані |
| created_at | timestamp | Дата створення |
| updated_at | timestamp | Дата оновлення |

---

## 📚 Документація

Додаткова документація знаходиться в:

```
docs/air-alert-service-guide.md           - Детальний гайд по Air Alert
docs/air-alert-telegram-integration.md    - Інтеграція з Telegram
docs/AIR-ALERT-IMPLEMENTATION-SUMMARY.md  - Підсумок імплементації
docs/FINAL-AIR-ALERT-SUMMARY.md           - Фінальний звіт
```

---

## ⚠️ Важливо

1. **Перевірте версії PHP та Laravel** у `mi-razom` - повинні бути сумісні
2. **Переконайтесь що GD або Imagick встановлені** для генерації зображень
3. **Налаштуйте черги** якщо хочете використовувати Jobs асинхронно
4. **Захистіть токени** - ніколи не комітьте `.env` в git

---

## 🆘 Можливі проблеми

### Помилка: "Class 'Intervention\Image\ImageManager' not found"
```bash
composer require intervention/image
```

### Помилка: "Call to undefined function imagecreate()"
```bash
# Ubuntu/Debian
sudo apt-get install php8.3-gd

# Перезапустіть PHP-FPM
sudo systemctl restart php8.3-fpm
```

### Помилка: "Unable to create directory storage/app/power-outage-images"
```bash
chmod -R 775 storage
chown -R www-data:www-data storage
```

### Помилка: "Unable to read font"
```bash
# Встановіть шрифти DejaVu
sudo apt-get install -y fonts-dejavu fonts-dejavu-core

# Перевірте, чи шрифти встановлені
fc-list | grep DejaVu
```

---

## ✅ Чеклист міграції

- [ ] Скопійовано всі файли
- [ ] Додано конфігурацію в `services.php`
- [ ] Додано змінні в `.env`
- [ ] Встановлено Composer пакети
- [ ] Виконано міграції
- [ ] Створено директорію для зображень
- [ ] Налаштовано Cron
- [ ] Протестовано Telegram бот
- [ ] Протестовано Air Alert
- [ ] Протестовано Power Outage
- [ ] Перевірено логи

---

**Автор:** AI Assistant  
**Дата:** 2025-11-11  
**Проект:** task.famhub.local → mi-razom

