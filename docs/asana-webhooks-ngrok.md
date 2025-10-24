# 🚀 Швидке налаштування WebHooks (Локальна розробка)

## Проблема
```
Помилка при створенні webhook: Invalid Request
```

**Причина:** Asana вимагає публічний HTTPS URL, а `http://task.famhub.local` недоступний з інтернету.

## ✅ Рішення: Публічний тунель (ngrok або альтернативи)

## 🎯 РЕКОМЕНДОВАНО: localtunnel (без реєстрації!)

### 1. Встановіть localtunnel

```bash
npm install -g localtunnel
```

### 2. Запустіть ваш додаток

### 2. Запустіть ваш додаток

Якщо використовуєте Docker:
```bash
docker-compose up -d
```

Або через artisan serve:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 3. Запустіть localtunnel (в новому терміналі)

```bash
# Для Docker (порт 80)
lt --port 80

# Для php artisan serve (порт 8000)
lt --port 8000

# З кастомним subdomain (може бути зайнятий)
lt --port 80 --subdomain mytaskapp
```

Ви побачите:
```
your url is: https://some-random-name.loca.lt
```

**Скопіюйте URL:** `https://some-random-name.loca.lt`

### 4. Перший запит - відкрийте URL в браузері

⚠️ **ВАЖЛИВО:** При першому використанні localtunnel покаже сторінку підтвердження.
Відкрийте URL в браузері та натисніть "Continue". Після цього тунель працюватиме.

### 5. Запустіть queue worker (в новому терміналі)

```bash
php artisan queue:work
```

### 6. Створіть webhook з localtunnel URL

```bash
# Приклад (замініть на ваш localtunnel URL)
php artisan asana:webhooks create \
  --resource=1203674070841321 \
  --url=https://some-random-name.loca.lt/api/webhooks/asana
```

---

## 🔧 Альтернатива 1: ngrok (потребує реєстрації)

### Налаштування ngrok

1. **Зареєструйтеся:** https://dashboard.ngrok.com/signup
2. **Отримайте authtoken:** https://dashboard.ngrok.com/get-started/your-authtoken
3. **Додайте token:**
```bash
ngrok config add-authtoken YOUR_AUTH_TOKEN
```

### Використання ngrok

```bash
# Якщо використовуєте Docker (порт 80)
ngrok http 80

# Якщо використовуєте php artisan serve (порт 8000)
ngrok http 8000
```

### Використання ngrok

```bash
# Якщо використовуєте Docker (порт 80)
ngrok http 80

# Якщо використовуєте php artisan serve (порт 8000)
ngrok http 8000
```

Ви побачите:
```
Forwarding  https://abc123xyz.ngrok.io -> http://localhost:80
```

**Переваги ngrok:**
- ✅ Статичний subdomain (платний план)
- ✅ Web Interface з детальними логами: http://127.0.0.1:4040
- ✅ Більш стабільний

---

## 🔧 Альтернатива 2: Cloudflare Tunnel (безкоштовний)

```bash
# Встановіть cloudflared
wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared-linux-amd64.deb

# Запустіть тунель
cloudflared tunnel --url http://localhost:80
```

---

## 📋 Загальні кроки після запуску тунелю

---

## 📋 Загальні кроки після запуску тунелю

### 1. Перевірте створені webhooks

```bash
php artisan asana:webhooks list
```

### 2. Тестування

### 2. Тестування

1. Зробіть зміну в Asana (оновіть таск в проєкті)
2. Перевірте логи:
```bash
tail -f storage/logs/laravel.log | grep webhook
```

---

## 🎯 Створення webhooks для всіх проектів

```bash
# Після запуску тунелю, отримайте URL та виконайте:
php artisan tinker

# В tinker (замініть на ваш URL):
$tunnelUrl = 'https://your-tunnel-url.loca.lt'; // або .ngrok.io, або інший

foreach(\App\Models\Project::whereNotNull('asana_id')->get() as $p) {
    try {
        $service = app(\App\Services\AsanaService::class);
        $webhook = $service->createWebhook(
            $p->asana_id, 
            $tunnelUrl . '/api/webhooks/asana'
        );
        echo "✓ Webhook created for: {$p->name}\n";
    } catch (\Exception $e) {
        echo "✗ Failed for {$p->name}: {$e->getMessage()}\n";
    }
}
```

---

## 📊 Порівняння тунелів

| Тунель | Реєстрація | Стабільність | Web Interface | Рекомендація |
|--------|-----------|--------------|---------------|--------------|
| **localtunnel** | ❌ Ні | ⭐⭐⭐ | ❌ | ✅ Найпростіше для початку |
| **ngrok** | ✅ Так | ⭐⭐⭐⭐⭐ | ✅ Чудовий | ✅ Найкраще для розробки |
| **Cloudflare** | ✅ Так | ⭐⭐⭐⭐ | ❌ | ⭐ Для production |

---

## 📊 Моніторинг

### localtunnel
```bash
# Немає вбудованого Web Interface
# Використовуйте логи Laravel
tail -f storage/logs/laravel.log | grep webhook
```

### ngrok
Відкрийте в браузері: http://127.0.0.1:4040

Тут ви побачите всі запити, що приходять на ваш webhook!

---

---

## ⚠️ Важливо

1. **URL змінюється** при кожному перезапуску (безкоштовні плани)
2. **Webhook треба перестворювати** якщо URL змінився
3. **Видаліть старі webhooks** перед створенням нових:
   ```bash
   php artisan asana:webhooks delete-all
   ```
4. **localtunnel:** При першому підключенні відкрийте URL в браузері для підтвердження

---

---

## 🎉 Production

Для production використовуйте реальний домен з HTTPS:

```bash
php artisan asana:webhooks create \
  --resource=PROJECT_GID \
  --url=https://your-domain.com/api/webhooks/asana
```

---

## 🔧 Встановлення тунелів

### localtunnel (найпростіше)
```bash
npm install -g localtunnel
lt --port 80
```

### ngrok
```bash
# 1. Зареєструйтеся: https://dashboard.ngrok.com/signup
# 2. Отримайте token: https://dashboard.ngrok.com/get-started/your-authtoken
# 3. Додайте token:
ngrok config add-authtoken YOUR_AUTH_TOKEN
# 4. Запустіть:
ngrok http 80
```

### Cloudflare Tunnel
```bash
wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared-linux-amd64.deb
cloudflared tunnel --url http://localhost:80
```

---

## 📝 Приклад успішного створення

```bash
# З localtunnel
igor@ms:~/developer/task.famhub.local$ php artisan asana:webhooks create \
  --resource=1203674070841321 \
  --url=https://brave-cats-help.loca.lt/api/webhooks/asana

Створюю webhook для ресурсу 1203674070841321...
Target URL: https://brave-cats-help.loca.lt/api/webhooks/asana
✓ Webhook успішно створено!
  GID: 1234567890123456
  Resource: My Project (1203674070841321)
  Target: https://brave-cats-help.loca.lt/api/webhooks/asana
  Active: Yes
```

---

## 🐛 Troubleshooting

### "Invalid Request"
- ✅ Переконайтеся що використовуєте **HTTPS** URL (не HTTP)
- ✅ Переконайтеся що ngrok запущений
- ✅ Перевірте що додаток працює на вказаному порті

### "Webhook не активний"
- Перевірте що queue worker запущений
- Перевірте логи: `tail -f storage/logs/laravel.log`
- Перевірте ngrok Web Interface: http://127.0.0.1:4040

### "Resource не знайдено"
- Переконайтеся що використовуєте правильний Asana GID проєкту
- Перевірте: `php artisan tinker` -> `\App\Models\Project::whereNotNull('asana_id')->pluck('name', 'asana_id');`
