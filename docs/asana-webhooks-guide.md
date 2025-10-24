# Керівництво по WebHooks Asana

## Огляд

WebHooks дозволяють отримувати real-time оновлення з Asana без необхідності постійного опитування API. Коли в Asana відбуваються зміни (створення, оновлення, видалення тасків, коментарів тощо), Asana автоматично надсилає HTTP POST запит на ваш сервер.

## Налаштування

### 1. Переконайтеся, що ваш додаток доступний з інтернету

WebHooks вимагають, щоб ваш сервер був доступний з інтернету. Для локальної розробки:

- Використовуйте **ngrok**: `ngrok http 80`
- Використовуйте **Expose**: `expose share http://localhost`
- Використовуйте **Laravel Sail**: `./vendor/bin/sail share`

### 2. Додайте опціональну змінну в .env (для безпеки)

```bash
ASANA_WEBHOOK_SECRET=your-random-secret-string
```

Цю змінну можна використати для перевірки, що запит справді прийшов від Asana (через query параметр).

### 3. Переконайтеся, що queue worker запущений

WebHooks обробляються асинхронно через Jobs:

```bash
php artisan queue:work
```

## Використання Artisan команди

### Переглянути всі webhooks

```bash
php artisan asana:webhooks list
```

### Створити webhook для проєкту

```bash
# Базова команда (використовує APP_URL автоматично)
php artisan asana:webhooks create --resource=1234567890123456

# З кастомним URL
php artisan asana:webhooks create --resource=1234567890123456 --url=https://your-app.com/api/webhooks/asana
```

**Параметри:**
- `--resource` - GID проєкту, портфоліо або workspace з Asana
- `--url` - (опціонально) URL для webhook. По замовчуванню: `{APP_URL}/api/webhooks/asana`

### Створити webhooks для всіх проєктів

```bash
# Отримати список проектів
php artisan tinker
>>> $projects = \App\Models\Project::whereNotNull('asana_id')->get();
>>> foreach($projects as $project) { 
      echo "Project: {$project->name} - Asana ID: {$project->asana_id}\n"; 
    }

# Створити webhook для кожного проєкту
php artisan asana:webhooks create --resource={ASANA_PROJECT_ID}
```

### Видалити конкретний webhook

```bash
php artisan asana:webhooks delete --webhook=1234567890123456
```

### Видалити всі webhooks

```bash
php artisan asana:webhooks delete-all
```

## Як працює WebHook

### 1. Handshake (перевірка при створенні)

Коли ви створюєте webhook, Asana надсилає запит з заголовком `X-Hook-Secret`. Ваш контролер повертає цей же secret назад, підтверджуючи, що URL доступний.

### 2. Події від Asana

Коли відбувається подія (створення/оновлення/видалення таску), Asana надсилає POST запит на ваш URL:

```json
{
  "events": [
    {
      "action": "changed",
      "resource": {
        "gid": "1234567890123456",
        "resource_type": "task"
      },
      "parent": null,
      "created_at": "2025-10-15T12:00:00.000Z"
    }
  ]
}
```

### 3. Обробка подій

`AsanaWebhookController` приймає запит і швидко відповідає `200 OK`, а потім відправляє події в чергу через `ProcessAsanaWebhookJob`.

### 4. Типи подій, що обробляються

- **Tasks**: 
  - `added` - новий таск створено
  - `changed` - таск оновлено
  - `deleted` - таск видалено
  
- **Projects**: 
  - `added` - новий проєкт створено
  - `changed` - проєкт оновлено
  - `deleted` - проєкт видалено

- **Stories (коментарі)**: 
  - `added` - новий коментар додано

- **Sections**: 
  - `added` - нова секція створена
  - `changed` - секція оновлена
  - `deleted` - секція видалена

## Що відбувається при отриманні події

### Task Events

- **added/changed**: Система отримує повні деталі таску з Asana і створює/оновлює його в базі даних, включаючи:
  - Назву і опис
  - Проєкт і секцію
  - Виконавця
  - Статус на основі секції
  - Дедлайн
  
- **deleted**: Таск видаляється з локальної бази даних

### Story (Comment) Events

- **added**: Якщо це текстовий коментар, він додається до відповідного таску в базі даних

## Тестування WebHooks

### 1. Перевірка логів

```bash
tail -f storage/logs/laravel.log
```

Шукайте записи:
- `Asana webhook received`
- `Processing Asana webhook`
- `Task synced from webhook`

### 2. Ручна перевірка через тінкер

```bash
php artisan tinker

>>> $service = app(\App\Services\AsanaService::class);
>>> $webhooks = $service->getWebhooks(config('services.asana.workspace_id'));
>>> $webhooks
```

### 3. Тестування локально через ngrok

```bash
# Запустити ngrok
ngrok http 80

# Використати ngrok URL при створенні webhook
php artisan asana:webhooks create --resource=PROJECT_GID --url=https://abc123.ngrok.io/api/webhooks/asana

# Зробити зміну в Asana (оновити таск)
# Перевірити логи
tail -f storage/logs/laravel.log
```

## Безпека

### 1. CSRF Protection

Route `/api/webhooks/asana` виключено з CSRF перевірки в `bootstrap/app.php`.

### 2. Опціональна верифікація через Secret

Ви можете додати query параметр `?secret=xxx` до webhook URL і перевіряти його:

```bash
# При створенні webhook
php artisan asana:webhooks create --resource=PROJECT_GID --url=https://your-app.com/api/webhooks/asana?secret=your-secret

# В .env
ASANA_WEBHOOK_SECRET=your-secret
```

### 3. Перевірка IP (опціонально)

Можна додати whitelist IP-адрес Asana в контролері, якщо потрібна додаткова безпека.

## Troubleshooting

### Webhook не створюється

**Проблема:** Помилка при створенні webhook
**Рішення:**
1. Перевірте, що URL доступний з інтернету
2. Перевірте, що ASANA_TOKEN налаштований правильно
3. Перевірте логи: `storage/logs/laravel.log`

### Події не обробляються

**Проблема:** Webhook створено, але події не обробляються
**Рішення:**
1. Переконайтеся, що `queue:work` запущений
2. Перевірте логи черги: `tail -f storage/logs/laravel.log`
3. Перевірте, чи надходять запити: перевірте логи веб-сервера (nginx/apache)

### Дублювання тасків

**Проблема:** Таски дублюються після обробки webhook
**Рішення:**
Використовується `Task::withoutEvents()` для запобігання спрацювання Observer під час синхронізації з webhook, що запобігає циклічним оновленням.

### Webhook втратив активність

**Проблема:** Webhook перестав працювати через деякий час
**Рішення:**
Asana деактивує webhooks, якщо вони повертають помилки. Перевірте статус:

```bash
php artisan asana:webhooks list
```

Якщо webhook неактивний, видаліть його і створіть знову:

```bash
php artisan asana:webhooks delete --webhook=WEBHOOK_GID
php artisan asana:webhooks create --resource=RESOURCE_GID
```

## Моніторинг

### Перевірка активності webhooks

```bash
php artisan asana:webhooks list
```

Колонка "Active" повинна показувати ✓ для активних webhooks.

### Перевірка обробки в черзі

```bash
# Перевірити failed jobs
php artisan queue:failed

# Повторити невдалі jobs
php artisan queue:retry all
```

## Обмеження

1. **Rate Limits**: Asana має rate limits для API. При великій кількості подій може знадобитися throttling
2. **Handshake timeout**: Відповідь на handshake повинна бути протягом 30 секунд
3. **Event timeout**: Відповідь на події webhook повинна бути протягом 10 секунд (тому використовуємо Jobs)
4. **Automatic deactivation**: Якщо webhook повертає помилки багато разів, Asana його деактивує

## Альтернативи WebHooks

Якщо WebHooks не підходять (наприклад, немає публічного URL), можна:

1. **Scheduled Tasks**: Запускати синхронізацію по розкладу через `schedule:run`
2. **Manual Sync**: Кнопка "Синхронізувати" в інтерфейсі (вже реалізовано)
3. **Hybrid**: WebHooks для критичних проєктів + scheduled sync для інших

## Приклад налаштування для production

```bash
# 1. Створити webhooks для всіх активних проектів
php artisan tinker
>>> foreach(\App\Models\Project::whereNotNull('asana_id')->get() as $p) {
      try {
        $service = app(\App\Services\AsanaService::class);
        $webhook = $service->createWebhook($p->asana_id, config('app.url').'/api/webhooks/asana');
        echo "✓ Webhook created for project: {$p->name}\n";
      } catch (\Exception $e) {
        echo "✗ Failed for {$p->name}: {$e->getMessage()}\n";
      }
    }

# 2. Запустити queue worker як supervisor process
sudo supervisorctl start laravel-worker:*

# 3. Налаштувати моніторинг
# Додати cron для перевірки активності webhooks
# 0 * * * * cd /path/to/project && php artisan asana:webhooks list >> /var/log/webhooks-check.log 2>&1
```

## Ресурси

- [Asana API Documentation - Webhooks](https://developers.asana.com/docs/webhooks)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Events Documentation](https://laravel.com/docs/events)
