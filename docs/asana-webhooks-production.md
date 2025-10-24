# 🚀 Asana WebHooks - Production Setup (БЕЗ ngrok!)

## ✅ Для Production НЕ ПОТРІБЕН ngrok!

Якщо ваш додаток вже має **публічний домен з HTTPS**, ngrok/localtunnel **НЕ ПОТРІБНІ**.

---

## 🎯 Production Setup (Реальний домен)

### Передумови

✅ Ваш сервер доступний з інтернету  
✅ Налаштований HTTPS (SSL сертифікат)  
✅ Домен налаштований (наприклад: `https://task.yourdomain.com`)

### Крок 1: Перевірте APP_URL

```bash
# .env
APP_URL=https://task.yourdomain.com
```

### Крок 2: Запустіть queue worker

```bash
# Через supervisor (рекомендовано)
sudo supervisorctl start laravel-worker:*

# Або вручну (для тестування)
php artisan queue:work
```

### Крок 3: Створіть webhooks

```bash
# Автоматично використає APP_URL
php artisan asana:webhooks create --resource=PROJECT_GID

# Або явно вкажіть URL
php artisan asana:webhooks create \
  --resource=PROJECT_GID \
  --url=https://task.yourdomain.com/api/webhooks/asana
```

### Крок 4: Створіть webhooks для всіх проектів

#### Варіант 1: Використання консольної команди (рекомендовано)

```bash
# Автоматично створює webhooks для всіх проектів з asana_id
php artisan asana:webhooks:create-all

# Без підтвердження (для скриптів)
php artisan asana:webhooks:create-all --force

# З власним URL
php artisan asana:webhooks:create-all --url=https://your-domain.com/api/webhooks/asana
```

#### Варіант 2: Через tinker (старий спосіб)

```bash
php artisan tinker

# Використає APP_URL автоматично
$service = app(\App\Services\AsanaService::class);
$url = config('app.url') . '/api/webhooks/asana';

foreach(\App\Models\Project::whereNotNull('asana_id')->get() as $p) {
    try {
        $webhook = $service->createWebhook($p->asana_id, $url);
        echo "✓ {$p->name}\n";
    } catch (\Exception $e) {
        echo "✗ {$p->name}: {$e->getMessage()}\n";
    }
}
```

### Крок 5: Перевірка

```bash
# Список webhooks
php artisan asana:webhooks list

# Тестування - зробіть зміну в Asana
tail -f storage/logs/laravel.log | grep webhook
```

---

## 🔧 Налаштування Supervisor для Queue Worker

Створіть файл `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
```

Запустіть:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## 📊 Моніторинг Webhooks

### Перевірка активності

```bash
# Список всіх webhooks
php artisan asana:webhooks list

# Перевірка статистики в БД
php artisan tinker
>>> \App\Models\AsanaWebhook::select('resource_name', 'events_count', 'last_event_at', 'active')->get();
```

### Логи

```bash
# Webhooks
tail -f storage/logs/laravel.log | grep -i webhook

# Queue worker
tail -f storage/logs/worker.log
```

---

## 🛡️ Безпека

### 1. HTTPS обов'язковий

Asana вимагає HTTPS. Переконайтеся що SSL налаштований:

```bash
curl -I https://task.yourdomain.com/api/webhooks/asana
```

Повинно повернути `200 OK` або `405 Method Not Allowed` (POST потрібен).

### 2. Опціонально: Secret Token

```bash
# .env
ASANA_WEBHOOK_SECRET=your-random-secret-string-here
```

Додайте до URL при створенні:

```bash
php artisan asana:webhooks create \
  --resource=PROJECT_GID \
  --url=https://task.yourdomain.com/api/webhooks/asana?secret=your-secret
```

Контролер автоматично перевірятиме secret через `config('services.asana.webhook_secret')`.

---

## 🔄 Оновлення Webhooks

### Якщо змінився домен

```bash
# 1. Видаліть старі webhooks
php artisan asana:webhooks delete-all

# 2. Оновіть APP_URL в .env
APP_URL=https://new-domain.com

# 3. Створіть нові webhooks
php artisan tinker
# ... код створення з Кроку 4
```

---

## ✅ Що відбувається при події

```
┌─────────┐         ┌─────────────┐         ┌──────────────┐
│  Asana  │ ──────> │ Your Server │ ──────> │ Queue Worker │
└─────────┘  POST   └─────────────┘  Job    └──────────────┘
   Webhook      HTTPS /api/webhooks/      ProcessAsanaWebhookJob
                      asana                    ↓
                      ↓                 ┌──────────────┐
                200 OK ←──────────────  │   Database   │
                (швидко!)               │   Updated    │
                                        └──────────────┘
```

1. **Asana надсилає POST** на ваш HTTPS endpoint
2. **Контролер швидко відповідає** 200 OK (< 10 сек)
3. **Job додається в чергу** для асинхронної обробки
4. **Queue Worker обробляє** подію і оновлює БД

---

## 📈 Переваги Production Setup

✅ **Стабільність** - немає залежності від тунелів  
✅ **Швидкість** - пряме з'єднання  
✅ **Безпека** - ваш SSL сертифікат  
✅ **Моніторинг** - повний контроль над логами  
✅ **Масштабування** - можна додати більше queue workers  

---

## 🐛 Troubleshooting

### Webhook не створюється

**Помилка:** `Invalid Request`

**Рішення:**
1. Перевірте що домен доступний: `curl https://your-domain.com`
2. Перевірте HTTPS: `curl -I https://your-domain.com/api/webhooks/asana`
3. Перевірте ASANA_TOKEN в .env

### Webhook неактивний

Asana деактивує webhooks, якщо вони повертають помилки.

**Рішення:**
```bash
# Перевірте логи
tail -100 storage/logs/laravel.log | grep ERROR

# Видаліть і створіть знову
php artisan asana:webhooks delete --webhook=WEBHOOK_GID
php artisan asana:webhooks create --resource=PROJECT_GID
```

### Події не обробляються

**Рішення:**
```bash
# Перевірте що queue worker працює
sudo supervisorctl status laravel-worker:*

# Або запустіть вручну для тестування
php artisan queue:work --verbose

# Перевірте failed jobs
php artisan queue:failed
php artisan queue:retry all
```

---

## 📚 Додаткові ресурси

- **Asana API Webhooks:** https://developers.asana.com/docs/webhooks
- **Laravel Queues:** https://laravel.com/docs/queues
- **Supervisor Setup:** https://laravel.com/docs/queues#supervisor-configuration

---

## 🎉 Готово!

Тепер ваш додаток автоматично отримуватиме оновлення з Asana в real-time без будь-яких тунелів! 🚀
