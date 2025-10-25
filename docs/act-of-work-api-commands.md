# Команды для получения данных актов выполненных работ через API

## Описание

Консольные команды для работы с актами выполненных работ через внешний API:
- `app:fetch-act-of-work-list-from-api` - получение списка актов
- `app:fetch-act-of-work-detail-from-api` - получение деталей конкретного акта

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

# Комбинированное использование
php artisan app:fetch-act-of-work-list-from-api --format=table --save
```

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
ID акта для получения деталей:

```bash
php artisan app:fetch-act-of-work-detail-from-api --act-id=23
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

# Комбинированное использование
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --format=table --save

# Получить детали для нескольких актов подряд
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --save
php artisan app:fetch-act-of-work-detail-from-api --act-id=24 --save
php artisan app:fetch-act-of-work-detail-from-api --act-id=25 --save
```

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

- `0` (SUCCESS) - Данные успешно получены
- `1` (FAILURE) - Ошибка при получении данных

---

## Обработка ошибок

Команды обрабатывают следующие ситуации:
- Таймаут запроса (30 секунд)
- Ошибки HTTP (неуспешный статус код)
- Пустой ответ от API
- Исключения при запросе
- Отсутствие обязательного параметра `--act-id` (для команды деталей)

Все ошибки выводятся в консоль с подробным описанием.

---

## Интеграция с другими командами

Эти команды можно использовать совместно с командой для получения данных таймера:

```bash
# Получить данные таймера
php artisan app:fetch-timer-data-from-api --save

# Получить список актов
php artisan app:fetch-act-of-work-list-from-api --save

# Получить детали конкретных актов
php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --save
php artisan app:fetch-act-of-work-detail-from-api --act-id=24 --save
```

Все данные будут сохранены в директории `storage/app/` с соответствующими именами и метками времени.

---

## Автоматизация

Можно создать bash-скрипт для автоматического получения данных:

```bash
#!/bin/bash
# fetch-all-act-data.sh

echo "Fetching timer data..."
php artisan app:fetch-timer-data-from-api --save

echo "Fetching act of work list..."
php artisan app:fetch-act-of-work-list-from-api --save

echo "Fetching act details..."
for act_id in 23 24 25; do
    echo "Fetching details for act $act_id..."
    php artisan app:fetch-act-of-work-detail-from-api --act-id=$act_id --save
done

echo "All data fetched successfully!"
```

Использование:

```bash
chmod +x fetch-all-act-data.sh
./fetch-all-act-data.sh
```
