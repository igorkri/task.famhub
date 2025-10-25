# Команды для получения данных актов выполненных работ через API

## Описание

Консольные команды для работы с актами выполненных работ через внешний API:
- `app:fetch-act-of-work-list-from-api` - получение и импорт списка актов
- `app:fetch-act-of-work-detail-from-api` - получение и импорт деталей конкретного акта

Обе команды поддерживают импорт данных в базу данных с возможностью очистки таблиц перед импортом.

## Быстрый старт

### Импорт всех актов с деталями (рекомендуется)

```bash
php artisan app:fetch-act-of-work-list-from-api --import --with-details
```

Эта команда:
- 📥 Получит список всех актов из API
- 💾 Импортирует их в таблицу `act_of_works`
- 🔄 Автоматически получит и импортирует детали для каждого акта
- 📊 Покажет прогресс и статистику

### Полная очистка и импорт

```bash
php artisan app:fetch-act-of-work-list-from-api --import --with-details --truncate --no-interaction
```

---

## Команда: Список актов выполненных работ

### Использование

#### Базовое использование

Получить список всех актов:

```bash
php artisan app:fetch-act-of-work-list-from-api
```

#### Опции

##### `--url`
Указать собственный URL для API:

```bash
php artisan app:fetch-act-of-work-list-from-api --url=https://api.example.com/acts
```

##### `--save`
Сохранить полученные данные в JSON файл в `storage/app/`:

```bash
php artisan app:fetch-act-of-work-list-from-api --save
```

Файл будет сохранён с именем в формате: `act-of-work-list-YYYY-MM-DD_HH-mm-ss.json`

##### `--import`
Импортировать данные в базу данных (таблица `act_of_works`):

```bash
php artisan app:fetch-act-of-work-list-from-api --import
```

##### `--with-details`
Автоматически импортировать детали для каждого акта (использовать вместе с `--import`):

```bash
php artisan app:fetch-act-of-work-list-from-api --import --with-details
```

⚡ **Рекомендуется:** Использование `--with-details` автоматически загружает детали для всех актов, что упрощает полный импорт данных.

##### `--truncate`
Очистить таблицу `act_of_works` перед импортом (использовать вместе с `--import`):

```bash
# С подтверждением
php artisan app:fetch-act-of-work-list-from-api --import --truncate

# Без подтверждения (для автоматизации)
php artisan app:fetch-act-of-work-list-from-api --import --truncate --no-interaction
```

⚠️ **Внимание:** Флаг `--truncate` удалит ВСЕ записи из таблицы `act_of_works`!

##### `--format`
Выбрать формат вывода (`json` или `table`):

```bash
# Вывод в виде JSON (по умолчанию)
php artisan app:fetch-act-of-work-list-from-api --format=json

# Вывод в виде таблицы
php artisan app:fetch-act-of-work-list-from-api --format=table
```

#### Примеры

```bash
# Получить список актов и вывести как таблицу
php artisan app:fetch-act-of-work-list-from-api --format=table

# Получить список актов и сохранить в файл
php artisan app:fetch-act-of-work-list-from-api --save

# Импортировать данные в базу данных
php artisan app:fetch-act-of-work-list-from-api --import

# Импортировать акты вместе с их деталями (рекомендуется)
php artisan app:fetch-act-of-work-list-from-api --import --with-details

# Очистить таблицу и импортировать новые данные с деталями
php artisan app:fetch-act-of-work-list-from-api --import --with-details --truncate --no-interaction

# Импортировать и сохранить в файл
php artisan app:fetch-act-of-work-list-from-api --import --save

# Комбинированное использование
php artisan app:fetch-act-of-work-list-from-api --import --with-details --format=table --save
```

### Импорт данных

При использовании флага `--import`:
- Данные импортируются в таблицу `act_of_works`
- Используется `updateOrCreate` с ключами: `number` + `user_id`
- Показывается прогресс-бар импорта
- После завершения выводится статистика:
  - **Imported**: количество успешно импортированных записей
  - **Skipped**: количество пропущенных записей (отсутствует пользователь или номер акта)
  - **Errors**: количество ошибок при импорте

#### Правила импорта

1. **Обязательные поля:**
   - `number` - номер акта
   - `user_id` - существующий пользователь в базе

2. **Маппинг статусов:**
   - `pending` → `ActOfWork::STATUS_PENDING`
   - `in_progress` → `ActOfWork::STATUS_IN_PROGRESS`
   - `paid` → `ActOfWork::STATUS_PAID`
   - `partially_paid` → `ActOfWork::STATUS_PARTIALLY_PAID`
   - `cancelled` → `ActOfWork::STATUS_CANCELLED`
   - `archived` → `ActOfWork::STATUS_ARCHIVED`
   - `draft` → `ActOfWork::STATUS_DRAFT`
   - `done` → `ActOfWork::STATUS_DONE`

3. **Значения по умолчанию:**
   - `status`: `pending`
   - `total_amount`: `0`
   - `paid_amount`: `0`
   - `sort`: `0`
   - `telegram_status`: `pending`
   - `type`: `ActOfWork::TYPE_ACT`

---

## Команда: Детали акта выполненных работ

### Использование

#### Базовое использование

Получить детали акта по ID (обязательный параметр):

```bash
php artisan app:fetch-act-of-work-detail-from-api --act-id=23
```

#### Опции

##### `--act-id` (обязательный)
ID или номер акта для получения деталей:

```bash
php artisan app:fetch-act-of-work-detail-from-api --act-id=23
# Или по номеру акта
php artisan app:fetch-act-of-work-detail-from-api --act-id=ACT-001
```

##### `--url`
Указать собственный URL для API:

```bash
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --url=https://api.example.com/act-details
```

##### `--save`
Сохранить полученные данные в JSON файл в `storage/app/`:

```bash
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --save
```

Файл будет сохранён с именем в формате: `act-of-work-detail-{act_id}-YYYY-MM-DD_HH-mm-ss.json`

##### `--import`
Импортировать данные в базу данных (таблица `act_of_work_details`):

```bash
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --import
```

##### `--truncate`
Очистить таблицу `act_of_work_details` перед импортом (использовать вместе с `--import`):

```bash
# С подтверждением
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --import --truncate

# Без подтверждения (для автоматизации)
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --import --truncate --no-interaction
```

⚠️ **Внимание:** Флаг `--truncate` удалит ВСЕ записи из таблицы `act_of_work_details`!

##### `--format`
Выбрать формат вывода (`json` или `table`):

```bash
# Вывод в виде JSON (по умолчанию)
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --format=json

# Вывод в виде таблицы
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --format=table
```

#### Примеры

```bash
# Получить детали акта и вывести как таблицу
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --format=table

# Получить детали акта и сохранить в файл
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --save

# Импортировать детали акта в базу данных
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --import

# Очистить таблицу и импортировать новые детали
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --import --truncate --no-interaction

# Комбинированное использование
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --import --format=table --save

# Получить детали для нескольких актов подряд с импортом
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --import
php artisan app:fetch-act-of-work-detail-from-api --act-id=24 --import
php artisan app:fetch-act-of-work-detail-from-api --act-id=25 --import
```

### Импорт данных деталей

При использовании флага `--import`:
- **Требование:** Родительский акт должен существовать в таблице `act_of_works`
- Данные импортируются в таблицу `act_of_work_details`
- Используется `updateOrCreate` с ключами: `act_of_work_id` + `task_gid` + `project_gid`
- Показывается прогресс-бар импорта
- После завершения выводится статистика (Imported/Skipped/Errors)

#### Правила импорта деталей

1. **Обязательные условия:**
   - Родительский акт должен существовать в `act_of_works`
   - Хотя бы одно поле: `task_gid` или `project_gid`

2. **Поиск родительского акта:**
   - По номеру акта (`number`)
   - По ID акта (`id`)

3. **Значения по умолчанию:**
   - `amount`: `0`
   - `hours`: `0`

---

## Конфигурация

URL API по умолчанию можно настроить в файле `.env`:

```env
# Список актов выполненных работ
ACT_OF_WORK_LIST_API_URL=https://asana.masterok-market.com.ua/admin/api/act-of-work/list

# Детали акта выполненных работ
ACT_OF_WORK_DETAIL_API_URL=https://asana.masterok-market.com.ua/admin/api/act-of-work-detail/by-act

# Токен для API (если требуется)
ACT_OF_WORK_API_TOKEN=your_token_here
```

Если переменные не установлены, используются URL по умолчанию из `config/services.php`.

---

## Возвращаемые коды

- `0` (SUCCESS) - Данные успешно получены и/или импортированы
- `1` (FAILURE) - Ошибка при получении данных или отсутствует обязательный параметр

---

## Обработка ошибок

Команды обрабатывают следующие ситуации:
- Таймаут запроса (30 секунд)
- Ошибки HTTP (неуспешный статус код)
- Пустой ответ от API
- Исключения при запросе
- Отсутствие обязательного параметра `--act-id` (для команды деталей)
- Отсутствие родительского акта при импорте деталей
- Отсутствие пользователя при импорте акта
- Ошибки валидации данных

Все ошибки выводятся в консоль с подробным описанием.

---

## Полный рабочий процесс

### 1. Первичная загрузка данных

```bash
# Вариант 1: Импортировать акты и детали одной командой (рекомендуется)
php artisan app:fetch-act-of-work-list-from-api --import --with-details --save

# Вариант 2: Импортировать акты и детали отдельно
php artisan app:fetch-act-of-work-list-from-api --import --save
php artisan app:fetch-act-of-work-detail-from-api --act-id=ACT-001 --import
php artisan app:fetch-act-of-work-detail-from-api --act-id=ACT-002 --import
```

### 2. Обновление данных

```bash
# Обновить акты с деталями
php artisan app:fetch-act-of-work-list-from-api --import --with-details

# Обновить только акты (без деталей)
php artisan app:fetch-act-of-work-list-from-api --import

# Обновить детали конкретного акта
php artisan app:fetch-act-of-work-detail-from-api --act-id=ACT-001 --import
```

### 3. Полная пересинхронизация

```bash
# Очистить и импортировать заново все акты с деталями
php artisan app:fetch-act-of-work-list-from-api --import --with-details --truncate --no-interaction
```

---

## Интеграция с другими командами

Эти команды можно использовать совместно с командой для получения данных таймера:

```bash
# Получить и импортировать данные таймера
php artisan app:fetch-timer-data-from-api --import --save

# Получить и импортировать список актов с деталями (рекомендуется)
php artisan app:fetch-act-of-work-list-from-api --import --with-details --save

# Или импортировать детали отдельно для конкретных актов
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --import --save
php artisan app:fetch-act-of-work-detail-from-api --act-id=24 --import --save
```

Все данные будут сохранены в директории `storage/app/` с соответствующими именами и метками времени.

---

## Автоматизация

### Bash-скрипт для полного импорта

```bash
#!/bin/bash
# import-all-act-data.sh

echo "=== Starting full data import ==="

echo "[1/2] Importing timer data..."
php artisan app:fetch-timer-data-from-api --import --save --no-interaction
if [ $? -ne 0 ]; then
    echo "Error importing timer data"
    exit 1
fi

echo "[2/2] Importing acts with details..."
php artisan app:fetch-act-of-work-list-from-api --import --with-details --save --no-interaction
if [ $? -ne 0 ]; then
    echo "Error importing acts"
    exit 1
fi

echo "=== Import completed successfully! ==="
```

### Расписание Laravel (Scheduler)

Добавьте в `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

// Ежедневный импорт актов с деталями в 3:00
Schedule::command('app:fetch-act-of-work-list-from-api --import --with-details --no-interaction')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onSuccess(function () {
        Log::info('Acts with details imported successfully');
    })
    ->onFailure(function () {
        Log::error('Failed to import acts');
    });
```

Использование:

```bash
chmod +x import-all-act-data.sh
./import-all-act-data.sh
```

---

## Тестирование

Для команд написаны автоматические тесты:

```bash
# Тесты для списка актов
php artisan test --filter=FetchActOfWorkListFromApiTest

# Тесты для деталей актов
php artisan test --filter=FetchActOfWorkDetailFromApiTest

# Все тесты для актов
php artisan test --filter=FetchActOfWork
```

Тесты покрывают:
- Успешное получение данных
- Обработку ошибок API
- Сохранение в файл
- Импорт в базу данных
- Очистку таблиц
- Обновление существующих записей
- Пропуск невалидных записей

---

## Поиск и устранение проблем

### Импорт не работает

1. Проверьте существование пользователей:
```bash
php artisan tinker --execute="echo User::count();"
```

2. Проверьте структуру данных от API:
```bash
php artisan app:fetch-act-of-work-list-from-api --format=json
```

3. Запустите с отладкой:
```bash
php artisan app:fetch-act-of-work-list-from-api --import -vvv
```

### Детали акта не импортируются

1. Убедитесь что родительский акт существует:
```bash
php artisan tinker --execute="
echo 'Act exists: ' . (ActOfWork::where('number', 'ACT-001')->exists() ? 'Yes' : 'No');
"
```

2. Импортируйте сначала список актов:
```bash
php artisan app:fetch-act-of-work-list-from-api --import
```

### Ошибки подключения к API

1. Проверьте доступность API:
```bash
curl -I https://asana.masterok-market.com.ua/admin/api/act-of-work/list
```

2. Проверьте настройки в `.env`
3. Увеличьте таймаут в команде (по умолчанию 30 секунд)

---
