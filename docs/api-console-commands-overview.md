# API Console Commands - Общий обзор

Это набор консольных команд для получения данных из внешнего API Asana Masterok Market и управления данными Asana.

## Команды синхронизации Asana

### Обновление timestamps задач

```bash
php artisan asana:update-timestamps
```

**Документация:** [asana-update-timestamps-command.md](./asana-update-timestamps-command.md)

**Назначение:** Оновлення полів `created_at` і `updated_at` задач з даними з Asana API

**Опції:**
- `--task-id` - ID конкретної задачі для оновлення
- `--limit=100` - Максимальна кількість задач (за замовчуванням: 100)
- `--force` - Оновити всі задачі, навіть якщо timestamps вже встановлено

**Приклади:**
```bash
# Оновити задачі без правильних timestamps
php artisan asana:update-timestamps

# Оновити конкретну задачу
php artisan asana:update-timestamps --task-id=376

# Оновити всі задачі (масове оновлення)
php artisan asana:update-timestamps --force --limit=1000
```

---

## Доступні команди API

### 1. Команда получения данных таймера

```bash
php artisan app:fetch-timer-data-from-api --import
```

**Endpoint:** `https://asana.masterok-market.com.ua/admin/api/timer/list`

**Документация:** [timer-api-command.md](./timer-api-command.md)

**Назначение:** Получение и импорт списка записей времени из таймера

**Новые возможности:**
- ✅ Импорт в базу данных (`--import`)
- ✅ Очистка таблицы перед импортом (`--truncate`)
- ✅ Прогресс-бар и статистика импорта

---

### 2. Команда получения списка актов выполненных работ

```bash
php artisan app:fetch-act-of-work-list-from-api --import --with-details
```

**Endpoint:** `https://asana.masterok-market.com.ua/admin/api/act-of-work/list`

**Документация:** [act-of-work-api-commands.md](./act-of-work-api-commands.md)

**Назначение:** Получение и импорт списка всех актов выполненных работ

**Новые возможности:**
- ✅ Импорт в базу данных (`--import`)
- ⚡ **Автоматический импорт деталей для каждого акта (`--with-details`)**
- ✅ Очистка таблицы перед импортом (`--truncate`)
- ✅ Прогресс-бар и статистика импорта

---

### 3. Команда получения деталей акта выполненных работ

```bash
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --import
```

**Endpoint:** `https://asana.masterok-market.com.ua/admin/api/act-of-work-detail/by-act?act_id={id}`

**Документация:** [act-of-work-api-commands.md](./act-of-work-api-commands.md)

**Назначение:** Получение и импорт детальной информации о конкретном акте

**Новые возможности:**
- ✅ Импорт в базу данных (`--import`)
- ✅ Очистка таблицы перед импортом (`--truncate`)
- ✅ Прогресс-бар и статистика импорта

---

## Общие возможности всех команд

### Опции

Все команды поддерживают следующие опции:

| Опция | Описание | Пример |
|-------|----------|--------|
| `--url` | Кастомный URL для API | `--url=https://api.example.com/data` |
| `--save` | Сохранить данные в JSON файл | `--save` |
| `--import` | Импортировать данные в БД | `--import` |
| `--truncate` | Очистить таблицу перед импортом | `--truncate` |
| `--format` | Формат вывода (json/table) | `--format=table` |

Дополнительно для команды списка актов:

| Опция | Описание | Пример |
|-------|----------|--------|
| `--with-details` | Автоматически импортировать детали | `--with-details` |

Дополнительно для команды деталей акта:

| Опция | Описание | Обязательно | Пример |
|-------|----------|-------------|--------|
| `--act-id` | ID акта для получения деталей | Да | `--act-id=23` |

---

## Быстрый старт

### Пример 1: Импорт данных таймера

```bash
php artisan app:fetch-timer-data-from-api --import --format=table
```

### Пример 2: Полный импорт актов с деталями (рекомендуется)

```bash
php artisan app:fetch-act-of-work-list-from-api --import --with-details --save
```

### Пример 3: Импорт деталей конкретного акта

```bash
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --import
```

### Пример 4: Полная пересинхронизация (очистка и импорт)

```bash
# Очистить и импортировать все данные
php artisan app:fetch-timer-data-from-api --import --truncate --no-interaction
php artisan app:fetch-act-of-work-list-from-api --import --with-details --truncate --no-interaction
```

### Пример 3: Получить детали акта #23

```bash
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --format=table
```

---

## Конфигурация

Добавьте следующие переменные в файл `.env`:

```env
# Timer API
TIMER_API_URL=https://asana.masterok-market.com.ua/admin/api/timer/list
TIMER_API_TOKEN=

# Act of Work API
ACT_OF_WORK_LIST_API_URL=https://asana.masterok-market.com.ua/admin/api/act-of-work/list
ACT_OF_WORK_DETAIL_API_URL=https://asana.masterok-market.com.ua/admin/api/act-of-work-detail/by-act
ACT_OF_WORK_API_TOKEN=
```

---

## Batch скрипт для получения всех данных

Создайте файл `fetch-all-data.sh`:

```bash
#!/bin/bash

# Получение данных таймера
echo "📊 Fetching timer data..."
php artisan app:fetch-timer-data-from-api --save

# Получение списка актов
echo "📋 Fetching act of work list..."
php artisan app:fetch-act-of-work-list-from-api --save

# Получение деталей актов
echo "📄 Fetching act of work details..."

# Получите список ID актов (можно из первой команды или указать вручную)
act_ids=(23 24 25 26 27)

for act_id in "${act_ids[@]}"; do
    echo "  → Fetching details for act #$act_id..."
    php artisan app:fetch-act-of-work-detail-from-api --act-id=$act_id --save
done

echo "✅ All data fetched successfully!"
echo "📁 Files saved in: storage/app/"
```

Использование:

```bash
chmod +x fetch-all-data.sh
./fetch-all-data.sh
```

---

## Структура сохраняемых файлов

Все файлы сохраняются в директории `storage/app/` с следующими именами:

- `timer-api-YYYY-MM-DD_HH-mm-ss.json`
- `act-of-work-list-YYYY-MM-DD_HH-mm-ss.json`
- `act-of-work-detail-{act_id}-YYYY-MM-DD_HH-mm-ss.json`

Пример:
```
storage/app/
├── timer-api-2025-10-25_14-30-45.json
├── act-of-work-list-2025-10-25_14-31-12.json
├── act-of-work-detail-23-2025-10-25_14-32-01.json
└── act-of-work-detail-24-2025-10-25_14-32-15.json
```

---

## Обработка ошибок

Все команды возвращают соответствующие коды выхода:

- `0` - Успешное выполнение
- `1` - Ошибка (сеть, API, валидация)

Примеры обработки ошибок в скриптах:

```bash
php artisan app:fetch-timer-data-from-api --save
if [ $? -eq 0 ]; then
    echo "✅ Success"
else
    echo "❌ Error occurred"
fi
```

---

## Тестирование

Запустить все тесты для API команд:

```bash
php artisan test --filter="FetchTimerData|FetchActOfWork"
```

Или по отдельности:

```bash
# Тесты для команды таймера
php artisan test --filter=FetchTimerDataFromApiTest

# Тесты для команд актов
php artisan test --filter=FetchActOfWorkListFromApiTest
php artisan test --filter=FetchActOfWorkDetailFromApiTest
```

---

## Дополнительная информация

- **Таймаут запросов:** 30 секунд
- **Формат данных:** JSON
- **Кодировка:** UTF-8
- **HTTP клиент:** Laravel HTTP Client (Guzzle)

---

## Связанные файлы

- **Команды:** `app/Console/Commands/Fetch*FromApi.php`
- **Конфигурация:** `config/services.php`
- **Тесты:** `tests/Feature/Fetch*FromApiTest.php`
- **Документация:** `docs/*-api-*.md`
