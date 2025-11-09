# Документация системы мониторинга отключений электроэнергии

## Обзор

Эта папка содержит всю документацию по системе автоматического мониторинга и уведомлений о графиках отключений электроэнергии для Полтавской области.

## Документы

### Основные

- **[README.md](README.md)** - Краткое руководство пользователя
- **[QUICKSTART.md](QUICKSTART.md)** - Быстрый старт с детальными инструкциями
- **[monitor.md](monitor.md)** - Полная документация системы

### Справочные

- **[COMMANDS.md](COMMANDS.md)** - Полный список команд и примеров использования
- **[SUMMARY.md](SUMMARY.md)** - Итоги разработки и статистика
- **[CHANGELOG.md](CHANGELOG.md)** - История изменений

## Быстрый старт

```bash
# 1. Настройка Telegram
./scripts/setup-telegram.sh

# 2. Проверка системы
./scripts/test-power-outage.sh

# 3. Ручное получение графика
php artisan power:fetch-schedule
```

## Автоматизация

Система автоматически проверяет график каждые 10 минут через Laravel Scheduler.

Настройте cron:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Структура проекта

```
app/
├── Console/Commands/FetchPowerOutageSchedule.php
├── Jobs/SendPowerOutageNotification.php
├── Models/PowerOutageSchedule.php
└── Services/PowerOutageParserService.php

scripts/
├── setup-telegram.sh
└── test-power-outage.sh

docs/power-outage/
├── README.md (этот файл)
├── QUICKSTART.md
├── monitor.md
├── COMMANDS.md
├── SUMMARY.md
└── CHANGELOG.md
```

## Основные возможности

- ✅ Автоматическое получение данных каждые 10 минут
- ✅ Парсинг HTML в структурированные данные
- ✅ Сохранение истории изменений
- ✅ Уведомления в Telegram при обновлениях
- ✅ Детальный анализ по очередям и временным интервалам

## Поддержка

При возникновении проблем:

1. Проверьте [COMMANDS.md](COMMANDS.md) для troubleshooting
2. Просмотрите логи: `tail -f storage/logs/laravel.log | grep -i power`
3. Запустите тесты: `php artisan test --filter=PowerOutageScheduleTest`

## Версия

Текущая версия: 1.1.0  
Последнее обновление: 09.11.2025

