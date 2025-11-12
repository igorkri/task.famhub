# Інструкція з розгортання Viber Webhook на віддаленому сервері

## Шлях на сервері
```
/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub
```

## Крок 1: Завантажити оновлені файли на сервер

### Використовуючи rsync (рекомендовано):
```bash
# З локальної машини
rsync -avz --exclude 'vendor' --exclude 'node_modules' --exclude '.git' \
  /home/igor/developer/task.famhub.local/ \
  user@server:/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/
```

### Або використовуючи scp:
```bash
# Завантажити routes/web.php
scp /home/igor/developer/task.famhub.local/routes/web.php \
  user@server:/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/routes/web.php

# Завантажити bootstrap/app.php
scp /home/igor/developer/task.famhub.local/bootstrap/app.php \
  user@server:/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/bootstrap/app.php
```

### Або через Git (якщо використовується):
```bash
# На локальній машині
cd /home/igor/developer/task.famhub.local
git add routes/web.php bootstrap/app.php
git commit -m "Fix Viber webhook with proper logging"
git push

# На віддаленому сервері
cd /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub
git pull
```

## Крок 2: На віддаленому сервері виконати

```bash
# Підключитися до сервера
ssh user@server

# Перейти в директорію проекту
cd /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub

# Створити директорію для логів з правильними правами
mkdir -p storage/logs
chmod -R 777 storage/logs

# Очистити кеш Laravel
php artisan optimize:clear
# або окремо:
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

# Перевірити права доступу
ls -la storage/logs/

# Створити порожній файл логу (опціонально)
touch storage/logs/viber_webhook.log
chmod 666 storage/logs/viber_webhook.log
```

## Крок 3: Перевірити webhook в Viber

```bash
# Отримати поточну інформацію про webhook
curl -X POST \
  -H "X-Viber-Auth-Token: 479d6bb020e7d3c0-10c469c78149798d-5cc4db7f99be936f" \
  -H "Content-Type: application/json" \
  https://chatapi.viber.com/pa/get_account_info | jq '.webhook'
```

## Крок 4: Встановити/Оновити webhook в Viber

```bash
curl -X POST \
  -H "X-Viber-Auth-Token: 479d6bb020e7d3c0-10c469c78149798d-5cc4db7f99be936f" \
  -H "Content-Type: application/json" \
  -d '{
        "url": "https://task.dev2025.ingsot.com/viber/webhook",
        "event_types": ["delivered", "seen", "failed", "subscribed", "unsubscribed", "conversation_started", "message"]
      }' \
  https://chatapi.viber.com/pa/set_webhook
```

**Очікувана відповідь:**
```json
{
  "status": 0,
  "status_message": "ok",
  "event_types": ["delivered", "seen", "failed", "subscribed", "unsubscribed", "conversation_started", "message"]
}
```

## Крок 5: Тестування

### Тест 1: Перевірка доступності
```bash
curl https://task.dev2025.ingsot.com/ping
```
Очікується: `{"status":"ok","time":"..."}`

### Тест 2: GET запит на webhook
```bash
curl https://task.dev2025.ingsot.com/viber/webhook
```
Очікується: `{"status":0,"message":"Webhook endpoint is active"}`

### Тест 3: POST запит (симуляція Viber)
```bash
curl -X POST https://task.dev2025.ingsot.com/viber/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "event": "message",
    "timestamp": 1699999999999,
    "message_token": 12345,
    "sender": {
      "id": "TEST_ID_12345",
      "name": "Test User"
    },
    "message": {
      "type": "text",
      "text": "Тестове повідомлення"
    }
  }'
```
Очікується: `{"status":0,"message":"OK"}`

### Тест 4: Перевірка логів на сервері
```bash
# На віддаленому сервері
ssh user@server
cd /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub
tail -100 storage/logs/viber_webhook.log
```

### Тест 5: Реальний тест з Viber
1. Відкрийте бота в Viber
2. Відправте тестове повідомлення
3. Одразу перевірте логи:
```bash
tail -f storage/logs/viber_webhook.log
```

## Крок 6: Моніторинг в реальному часі

```bash
# На сервері запустіть моніторинг логів
ssh user@server
cd /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub
tail -f storage/logs/viber_webhook.log

# Тепер відправте повідомлення з Viber і спостерігайте за логами
```

## Типові проблеми та рішення

### Проблема 1: "Permission denied" при запису логів
```bash
# Рішення:
sudo chmod -R 777 storage/logs
sudo chown -R www-data:www-data storage/logs  # для Apache
# або
sudo chown -R nginx:nginx storage/logs  # для Nginx
```

### Проблема 2: Webhook повертає 500 помилку
```bash
# Перевірте логи Laravel:
tail -100 storage/logs/laravel.log

# Перевірте права доступу:
ls -la storage/

# Очистіть кеш:
php artisan optimize:clear
```

### Проблема 3: Viber не надсилає запити
- Переконайтесь що домен доступний ззовні
- Перевірте SSL сертифікат (має бути валідним)
- Переконайтесь що webhook встановлений коректно (Крок 4)
- Перевірте firewall

### Проблема 4: Логи не створюються
```bash
# Створіть вручну:
mkdir -p storage/logs
touch storage/logs/viber_webhook.log
chmod 666 storage/logs/viber_webhook.log

# Перевірте, що PHP може писати в цю директорію:
php -r "file_put_contents('storage/logs/test.txt', 'test');"
cat storage/logs/test.txt
```

## Структура логів

Кожен запит логується з такою інформацією:
- Timestamp (час запиту)
- HTTP Method (GET/POST)
- IP адреса відправника
- Всі HTTP headers
- Всі дані запиту
- Raw content (сире тіло запиту)

Для повідомлень додатково логується:
- Ім'я користувача
- User ID
- Текст повідомлення
- Відповідь від Viber API

## Важливо!

1. **Після кожного оновлення коду** на сервері виконуйте `php artisan optimize:clear`
2. **Токен Viber** зберігайте в безпеці, не публікуйте його
3. **Права доступу** до storage/logs повинні дозволяти веб-серверу писати файли
4. **SSL сертифікат** має бути валідним (Viber не працює з самопідписаними)

## Швидкий checklist

- [ ] Файли завантажені на сервер
- [ ] Виконано `php artisan optimize:clear` на сервері
- [ ] storage/logs має правильні права доступу (777)
- [ ] Webhook встановлений в Viber (`set_webhook`)
- [ ] Тест з curl працює
- [ ] Файл логу створюється при тесті з curl
- [ ] Відправлено реальне повідомлення з Viber
- [ ] Лог з'являється від реального Viber запиту

Якщо всі пункти виконані і логи від Viber не з'являються - проблема в доступності сервера або SSL сертифікаті.

