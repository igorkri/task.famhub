# ⚡ Швидка шпаргалка - Автоматичний запуск графіка відключень

## 🎯 Одна команда для всього

```bash
./scripts/check-power-schedule.sh
```

Ця команда покаже вам **все**, що потрібно знати про налаштування.

---

## 🚀 Найшвидше налаштування (3 кроки)

### 1. Додайте в cron
```bash
crontab -e
```

### 2. Вставте цей рядок

**Development:**
```
* * * * * cd /home/igor/developer/task.famhub.local && php8.4 artisan schedule:run >> /dev/null 2>&1
```

**Production:**
```
* * * * * cd /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub && php8.4 artisan schedule:run >> /dev/null 2>&1
```

### 3. Збережіть
`Ctrl+O` → `Enter` → `Ctrl+X`

**Готово! Система працює автоматично.**

---

## 📋 Базові команди

```bash
# Перевірити налаштування
./scripts/check-power-schedule.sh

# Показати заплановані завдання
php artisan schedule:list

# Запустити scheduler вручну
php artisan schedule:run

# Отримати графік вручну
php artisan power:fetch-schedule

# Переглянути логи
tail -f storage/logs/laravel.log | grep -i power
```

---

## 📖 Документація

- **Швидкий довідник:** `docs/power-outage/AUTO-SCHEDULE-QUICKREF.md`
- **Детальна інструкція:** `docs/power-outage/AUTO-SCHEDULE-SETUP.md`
- **Приклади для серверів:** `docs/power-outage/SERVER-SETUP-EXAMPLES.md`

---

## 🔧 Зміна інтервалу

Файл: `routes/console.php`

```php
// Поточне:
->everyTenMinutes()

// Доступні:
->everyFiveMinutes()
->everyFifteenMinutes()
->hourly()
->dailyAt('08:00')
```

---

## ❓ Проблеми?

1. Запустіть: `./scripts/check-power-schedule.sh`
2. Перегляньте логи: `tail -f storage/logs/laravel.log`
3. Читайте: `docs/power-outage/AUTO-SCHEDULE-SETUP.md#troubleshooting`

---

**Збережіть цей файл для швидкого доступу!**

