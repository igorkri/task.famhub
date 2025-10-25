# Команда для получения данных таймера через API

## Описание

Консольная команда `app:fetch-timer-data-from-api` предназначена для получения данных таймера из внешнего API.

## Использование

### Базовое использование

Получить данные с URL по умолчанию:

```bash
php artisan app:fetch-timer-data-from-api
```

### Опции

#### `--url`
Указать собственный URL для API:

```bash
php artisan app:fetch-timer-data-from-api --url=https://api.example.com/timer/data
```

#### `--save`
Сохранить полученные данные в JSON файл в `storage/app/`:

```bash
php artisan app:fetch-timer-data-from-api --save
```

Файл будет сохранён с именем в формате: `timer-api-YYYY-MM-DD_HH-mm-ss.json`

#### `--format`
Выбрать формат вывода (`json` или `table`):

```bash
# Вывод в виде JSON (по умолчанию)
php artisan app:fetch-timer-data-from-api --format=json

# Вывод в виде таблицы
php artisan app:fetch-timer-data-from-api --format=table
```

### Комбинированное использование

```bash
php artisan app:fetch-timer-data-from-api --format=table --save
```

## Конфигурация

URL API по умолчанию можно настроить в файле `.env`:

```env
TIMER_API_URL=https://asana.masterok-market.com.ua/admin/api/timer/list
TIMER_API_TOKEN=your_token_here
```

Если переменные не установлены, используется URL по умолчанию из `config/services.php`.

## Примеры

### Пример 1: Получить данные и вывести как JSON

```bash
php artisan app:fetch-timer-data-from-api
```

### Пример 2: Получить данные, вывести как таблицу и сохранить в файл

```bash
php artisan app:fetch-timer-data-from-api --format=table --save
```

### Пример 3: Использовать кастомный URL

```bash
php artisan app:fetch-timer-data-from-api --url=https://custom-api.example.com/data
```

## Возвращаемые коды

- `0` (SUCCESS) - Данные успешно получены
- `1` (FAILURE) - Ошибка при получении данных

## Обработка ошибок

Команда обрабатывает следующие ситуации:
- Таймаут запроса (30 секунд)
- Ошибки HTTP (неуспешный статус код)
- Пустой ответ от API
- Исключения при запросе

Все ошибки выводятся в консоль с подробным описанием.
