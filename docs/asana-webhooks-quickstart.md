# 🎯 Швидкий старт: WebHooks Asana

## ⚠️ ВАЖЛИВО: Коли потрібен ngrok?

| Ситуація | ngrok/localtunnel | Рішення |
|----------|------------------|---------|
| 🏠 **Локальна розробка** (task.famhub.local) | ✅ **ПОТРІБЕН** | Використайте localtunnel або ngrok |
| 🌐 **Production** (реальний домен з HTTPS) | ❌ **НЕ ПОТРІБЕН** | Використайте APP_URL |
| 🔧 **Staging** (публічний сервер) | ❌ **НЕ ПОТРІБЕН** | Використайте APP_URL |

---

## 🚀 ВИБІР СЦЕНАРІЮ

### 📘 Сценарій А: Production (Реальний домен)

**Якщо у вас вже є публічний домен з HTTPS:**

👉 **Читайте:** [docs/asana-webhooks-production.md](./asana-webhooks-production.md)

**Коротко:**
```bash
# 1. Запустіть queue worker
php artisan queue:work

# 2. Створіть webhook (використає APP_URL автоматично)
php artisan asana:webhooks create --resource=PROJECT_GID

# 3. Готово! ✅
```

---

### 📗 Сценарій Б: Локальна розробка (без публічного домену)

**Якщо розробляєте локально (task.famhub.local):**

👉 **Читайте:** [docs/asana-webhooks-ngrok.md](./asana-webhooks-ngrok.md)

**Коротко:**
```bash
# 1. Встановіть локальний тунель (один раз)
npm install -g localtunnel

# 2. Запустіть тунель (Термінал 1)
lt --port 80
# Скопіюйте HTTPS URL

# 3. Queue worker (Термінал 2)
php artisan queue:work

# 4. Створіть webhook з тунель URL (Термінал 3)
php artisan asana:webhooks create \
  --resource=PROJECT_GID \
  --url=https://YOUR-TUNNEL-URL.loca.lt/api/webhooks/asana
```

---

### 1️⃣ Перевірте налаштування

```bash
php artisan tinker
>>> config('app.url')  // Має бути https://your-domain.com
```

### 2️⃣ Створіть webhooks для production

```bash
php artisan asana:webhooks create \
  --resource=PROJECT_GID
  # --url не потрібен, використає APP_URL автоматично
```

### 3️⃣ Налаштуйте supervisor для queue worker

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

## 🔍 Що відбувається коли приходить подія

1. **Asana надсилає POST** на `/api/webhooks/asana`
2. **Контролер швидко відповідає** 200 OK (важливо для Asana!)
3. **Job додається в чергу** `ProcessAsanaWebhookJob`
4. **Queue worker обробляє** подію:
   - Для **tasks**: синхронізує з Asana (create/update/delete)
   - Для **stories**: створює коментарі
   - Для **projects/sections**: логує події
5. **Статистика оновлюється** в таблиці `asana_webhooks`

## 🎉 Переваги WebHooks

- ⚡ **Real-time синхронізація** - зміни з'являються миттєво
- 📉 **Менше навантаження на API** - не потрібно постійно опитувати
- 🎯 **Точність** - отримуєте тільки реальні зміни
- 🔄 **Двостороння синхронізація** - працює разом з існуючою синхронізацією

## 📚 Додаткова документація

Детальна документація: [docs/asana-webhooks-guide.md](./asana-webhooks-guide.md)

Там ви знайдете:
- Повний опис всіх типів подій
- Troubleshooting
- Безпека та верифікація
- Production deployment
- Моніторинг та обмеження

## 🐛 Troubleshooting

### Webhook не створюється
- Перевірте, що URL доступний з інтернету (для dev: ngrok)
- Перевірте ASANA_TOKEN

### Події не обробляються
- Запустіть `php artisan queue:work`
- Перевірте логи: `tail -f storage/logs/laravel.log`

### Webhook неактивний
Видаліть і створіть знову:
```bash
php artisan asana:webhooks delete --webhook=GID
php artisan asana:webhooks create --resource=PROJECT_GID
```

## 🎊 Готово!

Тепер ваші таски будуть автоматично оновлюватися при змінах в Asana! 🚀
