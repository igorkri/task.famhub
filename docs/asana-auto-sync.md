# Автоматична синхронізація з Asana

## 📋 Огляд

Система автоматичної синхронізації працює на **двох рівнях**:

1. **Webhooks (основний метод)** - миттєва синхронізація при змінах в Asana
2. **Cron (резервний метод)** - періодична перевірка застарілих даних

---

## 🔄 1. Синхронізація через Webhooks

### Як працює:

- ✅ **Автоматично** - при будь-яких змінах в Asana
- ⚡ **Миттєво** - webhook спрацьовує за ~1-2 секунди
- 📦 **Що синхронізується:**
  - Назва задачі
  - Опис
  - Виконавець
  - Статус (completed/active)
  - Дедлайн і дата початку
  - **Всі кастомні поля** (включаючи час, бюджет, пріоритет)
  - Проект і секція
  - Коментарі (stories)

### Налаштування:

Webhooks вже налаштовані! URL: `https://your-domain.com/api/webhooks/asana`

**Перевірити webhooks:**
```bash
php artisan asana:webhooks list
```

**Створити webhooks для всіх проектів:**
```bash
php artisan asana:webhooks:create-all
```

**Видалити всі webhooks:**
```bash
php artisan asana:webhooks delete-all
```

### Обробка:

1. Webhook надходить на `/api/webhooks/asana`
2. `AsanaWebhookController` приймає запит і повертає 200 OK
3. Job `ProcessAsanaWebhookJob` обробляє подію асинхронно через чергу
4. Дані синхронізуються в локальну БД

---

## ⏰ 2. Резервна синхронізація через Cron

### Навіщо потрібна:

- 🛡️ **Резервний механізм** - на випадок, якщо webhook не спрацював
- 🔧 **Відновлення після збоїв** - якщо сервер був недоступний
- 📊 **Періодична перевірка** - для консистентності даних

### Як працює:

**Автоматично** (через Laravel Scheduler):
- Запускається **кожні 6 годин**
- Синхронізує задачі, які не оновлювалися останні 6 годин
- Максимум 100 задач за раз
- Працює у фоновому режимі

**Вручну** (для тестування або масової синхронізації):

```bash
# Синхронізувати задачі, які не оновлювалися 24 години
php artisan asana:sync-tasks

# Синхронізувати задачі, які не оновлювалися 12 годин
php artisan asana:sync-tasks --hours=12

# Синхронізувати більше задач (до 200)
php artisan asana:sync-tasks --limit=200

# FORCE режим - синхронізувати ВСІ незавершені задачі
php artisan asana:sync-tasks --force --limit=500
```

### Налаштування Cron:

Laravel Scheduler вже налаштований у `routes/console.php`:

```php
Schedule::command('asana:sync-tasks --hours=6 --limit=100')
    ->everySixHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
```

**Переконайтеся, що крон запущений на сервері:**

```bash
crontab -e
```

Додайте рядок для production:
```
* * * * * /usr/bin/php8.4 /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/artisan schedule:run >> /dev/null 2>&1
```

Для локальної розробки:
```
* * * * * cd /home/igor/developer/task.famhub.local && php artisan schedule:run >> /dev/null 2>&1
```

Або для Docker/Sail:
```
* * * * * cd /path-to-your-project && ./vendor/bin/sail artisan schedule:run >> /dev/null 2>&1
```

---

## 🎯 Що синхронізується автоматично

### ✅ Завжди синхронізується (через webhooks):

| Поле | Опис |
|------|------|
| **Назва** | title |
| **Опис** | description |
| **Виконавець** | user_id |
| **Статус** | is_completed, status (на основі секції) |
| **Дедлайн** | deadline (due_on) |
| **Дата початку** | start_date (start_on) |
| **Кастомні поля** | Всі (text, number, date, enum) |
| **Проект** | project_id |
| **Секція** | section_id |
| **Коментарі** | stories (з типом comment) |

### 🔄 Конвертація даних:

- **Enum GID** - автоматично конвертується у string
- **Числові поля** - зберігаються як цілі числа (хвилини)
- **Дати** - конвертуються у формат Y-m-d

---

## 🧪 Тестування

### Перевірити роботу webhooks:

1. Змініть задачу в Asana
2. Перевірте логи:
```bash
tail -f storage/logs/laravel.log | grep "Asana webhook"
```

3. Перевірте чергу:
```bash
php artisan queue:work --verbose
```

### Перевірити резервну синхронізацію:

```bash
# Тестовий запуск з виводом прогресу
php artisan asana:sync-tasks --hours=24 --limit=10
```

---

## 🛠️ Налаштування черг

Для правильної роботи webhooks потрібна налаштована черга:

### Локальна розробка:

```bash
php artisan queue:work --verbose
```

### Продакшн (Supervisor):

Створіть файл `/etc/supervisor/conf.d/laravel-task-worker.conf`:

```ini
[program:laravel-task-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php8.4 /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/storage/logs/worker.log
stopwaitsecs=3600
```

**Керування Supervisor:**

```bash
# Перезавантажити конфігурацію
sudo supervisorctl reread
sudo supervisorctl update

# Запустити worker
sudo supervisorctl start laravel-task-worker:*

# Перезапустити worker
sudo supervisorctl restart laravel-task-worker:*

# Перевірити статус
sudo supervisorctl status laravel-task-worker:*

# Переглянути логи
sudo supervisorctl tail -f laravel-task-worker:laravel-task-worker_00 stdout
```

Або використовуйте Docker/Sail з `docker-compose.yml`.

---

## 📊 Моніторинг

### Перевірити статистику webhooks:

```bash
php artisan tinker
```

```php
// Переглянути всі webhooks
\App\Models\AsanaWebhook::all();

// Останні події
\App\Models\AsanaWebhook::where('last_success_at', '>', now()->subDay())->get();
```

### Перевірити логи:

```bash
# Всі події webhooks
tail -100 storage/logs/laravel.log | grep "webhook"

# Помилки синхронізації
tail -100 storage/logs/laravel.log | grep "ERROR"

# Синхронізація кастомних полів
tail -100 storage/logs/laravel.log | grep "custom fields"
```

---

## ⚠️ Важливо

1. **Черга повинна працювати** - без черги webhooks не оброблятимуться
2. **Крон повинен бути налаштований** - для резервної синхронізації
3. **Webhooks створені** - для кожного проекту має бути webhook
4. **Ngrok для локальної розробки** - Asana не може відправляти webhooks на localhost

---

## 🚀 Швидкий старт

1. **Перевірте webhooks:**
```bash
php artisan asana:webhooks list
```

2. **Створіть webhooks (якщо потрібно):**
```bash
php artisan asana:webhooks:create-all
```

3. **Запустіть чергу:**
```bash
php artisan queue:work
```

4. **Налаштуйте крон** (на сервері)

5. **Готово!** 🎉 Тепер всі зміни в Asana автоматично синхронізуються!

---

## 📝 Примітки

- Webhooks працюють **тільки для проектів з налаштованими webhooks**
- Резервна синхронізація працює **для всіх незавершених задач**
- Кастомні поля синхронізуються **автоматично** при будь-якій зміні задачі
- При створенні нової задачі в Asana вона **автоматично з'являється** в системі

