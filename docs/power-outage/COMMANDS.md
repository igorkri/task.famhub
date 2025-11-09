# Команды для работы с системой мониторинга отключений

## Быстрый старт

```bash
# 1. Настройка Telegram (интерактивный скрипт)
./setup-telegram.sh

# 2. Проверка системы
./test-power-outage.sh

# 3. Добавить в crontab:
* * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1
```

## Получение данных

```bash
# График на сегодня
php artisan power:fetch-schedule

# График на конкретную дату (формат DD-MM-YYYY)
php artisan power:fetch-schedule 09-11-2025
php artisan power:fetch-schedule 10-11-2025
```

## Тестирование

```bash
# Запуск всех тестов
php artisan test --filter=PowerOutageScheduleTest

# Тестовая отправка в Telegram
php artisan tinker --execute="App\Jobs\SendPowerOutageNotification::dispatch(App\Models\PowerOutageSchedule::latest()->first());"
```

## Просмотр данных

```bash
# Последний график
php artisan tinker --execute="dd(App\Models\PowerOutageSchedule::latest()->first()->toArray());"

# Количество записей
php artisan tinker --execute="echo App\Models\PowerOutageSchedule::count();"

# Все графики за сегодня
php artisan tinker --execute="dd(App\Models\PowerOutageSchedule::whereDate('schedule_date', today())->get()->toArray());"
```

## Логи и мониторинг

```bash
# Просмотр логов в реальном времени
tail -f storage/logs/laravel.log | grep -i power
tail -f storage/logs/laravel.log | grep -i telegram
tail -f storage/logs/laravel.log | grep -i 'power\|telegram'

# Список запланированных команд
php artisan schedule:list

# Проверка работы планировщика
php artisan schedule:work
```

## База данных

```bash
# Посмотреть структуру таблицы
php artisan tinker --execute="
\$table = DB::select('PRAGMA table_info(power_outage_schedules)');
print_r(\$table);
"

# Очистить таблицу (для тестирования)
php artisan tinker --execute="App\Models\PowerOutageSchedule::truncate();"

# Количество записей
php artisan tinker --execute="echo App\Models\PowerOutageSchedule::count() . ' записей в БД';"
```

## API запросы (для отладки)

```bash
# Прямой запрос к API
curl -X POST "https://www.poe.pl.ua/customs/newgpv-info.php" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d 'seldate={"date_in":"09-11-2025"}' \
     -o response.html

# Просмотр ответа
cat response.html | less
```

## Анализ данных

```bash
# Статистика по очередям для последнего графика
php artisan tinker --execute="
\$schedule = App\Models\PowerOutageSchedule::latest()->first();
if (\$schedule) {
    echo 'График на: ' . \$schedule->schedule_date->format('d.m.Y') . PHP_EOL;
    echo 'Периодов: ' . count(\$schedule->periods) . PHP_EOL;
    echo 'Очередей: ' . count(\$schedule->schedule_data) . PHP_EOL;
    echo PHP_EOL;
    foreach (\$schedule->schedule_data as \$row) {
        \$offCount = count(array_filter(\$row['hourly_status'], fn(\$s) => \$s === 'off'));
        \$offHours = \$offCount / 2;
        echo \$row['queue'] . '.' . \$row['subqueue'] . ': ' . \$offHours . 'ч отключений' . PHP_EOL;
    }
}
"

# Сравнение изменений
php artisan tinker --execute="
\$schedules = App\Models\PowerOutageSchedule::whereDate('schedule_date', today())->orderBy('fetched_at')->get();
foreach (\$schedules as \$i => \$schedule) {
    echo 'Версия ' . (\$i + 1) . ' - ' . \$schedule->fetched_at . ' - Hash: ' . \$schedule->hash . PHP_EOL;
}
"
```

## Telegram

```bash
# Проверка настроек Telegram
grep TELEGRAM .env

# Тест отправки через API (замените YOUR_BOT_TOKEN и YOUR_CHAT_ID)
curl -X POST "https://api.telegram.org/botYOUR_BOT_TOKEN/sendMessage" \
     -d "chat_id=YOUR_CHAT_ID" \
     -d "text=Test message"

# Получить обновления бота (для получения chat_id)
curl "https://api.telegram.org/botYOUR_BOT_TOKEN/getUpdates"
```

## Обслуживание

```bash
# Проверка размера БД
du -h database/database.sqlite

# Очистка старых записей (старше 30 дней)
php artisan tinker --execute="App\Models\PowerOutageSchedule::where('schedule_date', '<', now()->subDays(30))->delete();"

# Пересчет статистики
php artisan tinker --execute="
echo 'Всего записей: ' . App\Models\PowerOutageSchedule::count() . PHP_EOL;
echo 'Уникальных дат: ' . App\Models\PowerOutageSchedule::distinct('schedule_date')->count('schedule_date') . PHP_EOL;
echo 'Первая запись: ' . App\Models\PowerOutageSchedule::oldest()->value('fetched_at') . PHP_EOL;
echo 'Последняя запись: ' . App\Models\PowerOutageSchedule::latest()->value('fetched_at') . PHP_EOL;
"
```

## Отладка

```bash
# Включить подробное логирование
APP_DEBUG=true php artisan power:fetch-schedule

# Проверка парсера на файле
php artisan tinker --execute="
\$html = file_get_contents('TODO/result.html');
\$parser = new App\Services\PowerOutageParserService();
\$data = \$parser->parse(\$html);
print_r(\$data);
"

# Проверка генерации hash
php artisan tinker --execute="
\$parser = new App\Services\PowerOutageParserService();
\$data = ['test' => 'data'];
echo \$parser->generateHash(\$data);
"
```

## Резервное копирование

```bash
# Экспорт данных
php artisan tinker --execute="
\$schedules = App\Models\PowerOutageSchedule::all();
file_put_contents('backup-schedules.json', json_encode(\$schedules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Exported to backup-schedules.json';
"

# Импорт данных
php artisan tinker --execute="
\$data = json_decode(file_get_contents('backup-schedules.json'), true);
foreach (\$data as \$item) {
    App\Models\PowerOutageSchedule::create(\$item);
}
echo 'Imported ' . count(\$data) . ' records';
"
```

## Полезные алиасы (добавить в ~/.bashrc)

```bash
alias power-fetch='php artisan power:fetch-schedule'
alias power-test='./test-power-outage.sh'
alias power-logs='tail -f storage/logs/laravel.log | grep -i power'
alias power-status='php artisan tinker --execute="echo App\Models\PowerOutageSchedule::count() . \" записей в БД\";"'
```

