# Реорганизация структуры проекта

## Что было сделано

Проект был реорганизован для улучшения структуры и устранения захламления корневой директории.

## Изменения

### 1. Создана папка `scripts/`

Все служебные скрипты перемещены в `scripts/`:

**Перемещено:**
- `setup-telegram.sh` → `scripts/setup-telegram.sh`
- `test-power-outage.sh` → `scripts/test-power-outage.sh`
- `db-manager.sh` → `scripts/db-manager.sh`
- `docker-manager.sh` → `scripts/docker-manager.sh`
- `fetch-all-api-data.sh` → `scripts/fetch-all-api-data.sh`
- `import-db-interactive.sh` → `scripts/import-db-interactive.sh`
- `import-db.sh` → `scripts/import-db.sh`
- `setup-storage.sh` → `scripts/setup-storage.sh`
- `start-ngrok.sh` → `scripts/start-ngrok.sh`
- `test-db-tools.sh` → `scripts/test-db-tools.sh`
- `webhook-setup-help.sh` → `scripts/webhook-setup-help.sh`
- `assign-role.php` → `scripts/assign-role.php`
- `test-badge.php` → `scripts/test-badge.php`
- `test-csv-encoding.php` → `scripts/test-csv-encoding.php`
- `test-import-duplicates.php` → `scripts/test-import-duplicates.php`
- `test-password-reset.php` → `scripts/test-password-reset.php`

**Создано:**
- `scripts/README.md` - Документация по всем скриптам

### 2. Создана папка `docs/power-outage/`

Вся документация по мониторингу отключений перемещена в `docs/power-outage/`:

**Перемещено:**
- `POWER-OUTAGE-README.md` → `docs/power-outage/README.md`
- `POWER-OUTAGE-QUICKSTART.md` → `docs/power-outage/QUICKSTART.md`
- `POWER-OUTAGE-COMMANDS.md` → `docs/power-outage/COMMANDS.md`
- `POWER-OUTAGE-SUMMARY.md` → `docs/power-outage/SUMMARY.md`
- `POWER-OUTAGE-CHANGELOG.md` → `docs/power-outage/CHANGELOG.md`
- `docs/power-outage-monitor.md` → `docs/power-outage/monitor.md`

**Создано:**
- `docs/power-outage/INDEX.md` - Обзор документации

### 3. Удалено

- `test-db-tools.sh~` - Временный файл

## Новая структура корня проекта

### До:
```
./
├── assign-role.php
├── db-manager.sh
├── docker-manager.sh
├── fetch-all-api-data.sh
├── import-db.sh
├── import-db-interactive.sh
├── POWER-OUTAGE-*.md (5 файлов)
├── setup-storage.sh
├── setup-telegram.sh
├── start-ngrok.sh
├── test-*.php (4 файла)
├── test-*.sh (2 файла)
├── webhook-setup-help.sh
└── ... (остальные файлы Laravel)
```

### После:
```
./
├── PROJECT-README.md         # Новый главный README
├── README.md                 # Оригинальный README
├── artisan
├── composer.json
├── package.json
├── phpunit.xml
├── docker-compose.yml
├── docs/                     # Документация
│   └── power-outage/        # Документация мониторинга
├── scripts/                  # Все скрипты
├── app/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
└── vendor/
```

## Обновленные пути

### Запуск скриптов

**Было:**
```bash
./test-power-outage.sh
./setup-telegram.sh
```

**Стало:**
```bash
./scripts/test-power-outage.sh
./scripts/setup-telegram.sh
```

### Документация

**Было:**
```
POWER-OUTAGE-README.md
POWER-OUTAGE-QUICKSTART.md
docs/power-outage-monitor.md
```

**Стало:**
```
docs/power-outage/README.md
docs/power-outage/QUICKSTART.md
docs/power-outage/monitor.md
docs/power-outage/INDEX.md
```

## Обратная совместимость

Все скрипты обновлены для работы из новой локации:
- Добавлен переход в корень проекта: `cd "$(dirname "$0")/.."`
- Все относительные пути обновлены

## Преимущества

✅ **Чистый корень проекта** - только необходимые конфигурационные файлы  
✅ **Логическая структура** - скрипты в `scripts/`, документация в `docs/`  
✅ **Простая навигация** - всё находится в понятных папках  
✅ **Масштабируемость** - легко добавлять новые скрипты и документы  
✅ **Профессиональный вид** - соответствует best practices Laravel  

## Проверка

Все скрипты и тесты проверены и работают корректно:

```bash
# Тесты
php artisan test --filter=PowerOutageScheduleTest
✅ 10/10 тестов проходят

# Скрипты
./scripts/test-power-outage.sh
✅ Работает корректно

# Команды
php artisan power:fetch-schedule
✅ Работает корректно
```

## Дата изменений

**Дата:** 09.11.2025  
**Версия:** 1.1.0

---

Все изменения задокументированы и протестированы! 🎉

