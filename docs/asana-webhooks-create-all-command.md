# 🎯 Команда `asana:webhooks:create-all`

## Опис

Автоматично створює Asana webhooks для **всіх проектів**, які мають `asana_id` в базі даних.

---

## Використання

### Базовий варіант (з підтвердженням)

```bash
php artisan asana:webhooks:create-all
```

Команда покаже список всіх проектів і попросить підтвердження перед створенням webhooks.

**Вивід:**
```
Знайдено проектів: 10
Target URL: https://famhub.net.ua/api/webhooks/asana

+----+------------------+------------------+
| ID | Назва            | Asana ID         |
+----+------------------+------------------+
| 1  | Base_tasks       | 1208368751405960 |
| 2  | Sixt             | 1208368767467057 |
| 3  | Yume Honda       | 1208368767467058 |
...
+----+------------------+------------------+

 Створити webhooks для всіх цих проектів? (yes/no) [yes]:
 >
```

---

## Опції

### `--force` - Без підтвердження

Пропускає запит на підтвердження. Корисно для автоматизації та скриптів.

```bash
php artisan asana:webhooks:create-all --force
```

### `--url=URL` - Власний URL

Використовує вказаний URL замість `APP_URL` з `.env`.

```bash
php artisan asana:webhooks:create-all --url=https://your-domain.com/api/webhooks/asana
```

### Комбінація опцій

```bash
php artisan asana:webhooks:create-all --force --url=https://custom-domain.com/api/webhooks/asana
```

---

## Що робить команда?

1. ✅ Знаходить всі проекти з `asana_id` в таблиці `projects`
2. ✅ Показує список проектів
3. ✅ Просить підтвердження (якщо немає `--force`)
4. ✅ Створює webhook для кожного проекту через Asana API
5. ✅ Зберігає інформацію про webhook в таблиці `asana_webhooks`
6. ✅ Показує прогрес-бар
7. ✅ Виводить підсумок: скільки створено успішно, скільки помилок

---

## Приклад виводу

### Успішне виконання

```bash
$ php artisan asana:webhooks:create-all --force

Знайдено проектів: 10
Target URL: https://famhub.net.ua/api/webhooks/asana

+----+------------------+------------------+
| ID | Назва            | Asana ID         |
+----+------------------+------------------+
| 1  | Base_tasks       | 1208368751405960 |
| 2  | Sixt             | 1208368767467057 |
...
+----+------------------+------------------+

 10/10 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

✓ Успішно створено: 10

✓ Webhooks успішно створено!
Перевірити список: php artisan asana:webhooks list
```

### З помилками

```bash
$ php artisan asana:webhooks:create-all --force

Знайдено проектів: 10
Target URL: https://famhub.net.ua/api/webhooks/asana

 10/10 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

✓ Успішно створено: 8
✗ Помилок: 2

Деталі помилок:
  • Sixt (1208368767467057): Webhook for this resource already exists
  • Iknet (1208368767467062): Invalid resource

✓ Webhooks успішно створено!
Перевірити список: php artisan asana:webhooks list
```

---

## Перевірка створених webhooks

Після виконання команди перевірте список webhooks:

```bash
php artisan asana:webhooks list
```

Або перегляньте записи в базі даних:

```bash
php artisan tinker
>>> \App\Models\AsanaWebhook::count();
>>> \App\Models\AsanaWebhook::latest()->get(['resource_name', 'target', 'active']);
```

---

## Видалення всіх webhooks

Якщо потрібно видалити всі webhooks перед створенням нових:

```bash
# Видалити всі webhooks
php artisan asana:webhooks delete-all

# Створити нові
php artisan asana:webhooks:create-all --force
```

---

## Типові помилки та рішення

### Помилка: "Webhook for this resource already exists"

**Причина:** Webhook для цього проекту вже існує в Asana.

**Рішення:** Видаліть існуючі webhooks перед створенням нових:
```bash
php artisan asana:webhooks delete-all
php artisan asana:webhooks:create-all --force
```

### Помилка: "Invalid Request"

**Причина:** URL недоступний для Asana або немає HTTPS.

**Рішення:**
1. Перевірте що `APP_URL` в `.env` містить HTTPS
2. Перевірте що сервер доступний з інтернету
3. Перевірте що endpoint `/api/webhooks/asana` працює

```bash
# Перевірка
curl -I https://your-domain.com/api/webhooks/asana
```

### Помилка: "ASANA_TOKEN not configured"

**Причина:** Відсутній Personal Access Token в `.env`.

**Рішення:** Додайте токен в `.env`:
```bash
ASANA_TOKEN=your_personal_access_token_here
```

---

## Автоматизація

### Додати в cron для періодичного оновлення

```bash
# Кожен понеділок о 00:00
0 0 * * 1 cd /path/to/project && php artisan asana:webhooks:create-all --force
```

### Використання в deployment scripts

```bash
#!/bin/bash
# deploy.sh

php artisan migrate --force
php artisan asana:webhooks delete-all
php artisan asana:webhooks:create-all --force
php artisan queue:restart
```

---

## Логи

Всі операції логуються в `storage/logs/laravel.log`:

```bash
# Перегляд логів створення webhooks
tail -f storage/logs/laravel.log | grep -i webhook
```

Успішне створення:
```
[2025-10-24 20:00:00] local.INFO: Created webhook for project {"project_id":1,"webhook_gid":"1211653869827122"}
```

Помилки:
```
[2025-10-24 20:00:00] local.ERROR: Failed to create webhook for project {"project_id":2,"error":"Webhook already exists"}
```

---

## Порівняння з іншими методами

| Метод | Переваги | Недоліки |
|-------|----------|----------|
| `asana:webhooks:create-all` | ✅ Автоматично<br>✅ Показує прогрес<br>✅ Обробляє помилки<br>✅ Зберігає в БД | Потрібен Laravel |
| Tinker (ручний цикл) | ✅ Гнучкість<br>✅ Можна налагоджувати | ❌ Ручний код<br>❌ Немає прогресу<br>❌ Складніше обробляти помилки |
| API напряму | ✅ Повний контроль | ❌ Складно<br>❌ Потрібен окремий скрипт<br>❌ Не інтегрується з Laravel |

---

## Додаткова інформація

- **Документація Asana API:** https://developers.asana.com/docs/webhooks
- **Інші команди для webhooks:**
  - `php artisan asana:webhooks list` - список всіх webhooks
  - `php artisan asana:webhooks create --resource=GID` - створити один webhook
  - `php artisan asana:webhooks delete --webhook=GID` - видалити один webhook
  - `php artisan asana:webhooks delete-all` - видалити всі webhooks

---

## Запитання?

Перегляньте основну документацію:
- [asana-webhooks-production.md](./asana-webhooks-production.md) - Production setup
- [asana-webhooks-quickstart.md](./asana-webhooks-quickstart.md) - Швидкий старт
- [asana-integration-guide.md](./asana-integration-guide.md) - Загальна інтеграція

