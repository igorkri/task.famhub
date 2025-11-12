# Viber Webhook - Виправлення проблеми з логуванням

## Проблема
Через curl логи записувались, але від реального Viber бота логів не було.

## Причина
Код використовував абсолютні шляхи для локальної машини (`/home/igor/developer/task.famhub.local/storage/logs/`), але сайт знаходиться на віддаленому сервері за шляхом `/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/`.

## Що було зроблено

### 1. Виправлено код (routes/web.php)
- Замінено абсолютні шляхи на `storage_path('logs/viber_webhook.log')`
- Додано обробку помилок з try-catch
- Додано детальне логування всіх запитів
- Додано GET роут для верифікації webhook
- Додано тестовий роут `/ping` для перевірки доступності

### 2. Виправлено CSRF захист (bootstrap/app.php)
- Додано `/viber/webhook` до виключень CSRF токену
- Viber тепер може відправляти POST запити без помилки 419

### 3. Створено документацію
- `docs/viber-webhook-debug.md` - детальна інструкція з налагодження
- `docs/viber-webhook-deployment.md` - інструкція з розгортання на сервері

### 4. Створено скрипти
- `scripts/test-viber-webhook.sh` - тестування webhook локально
- `scripts/deploy-viber-webhook.sh` - автоматичний деплой на сервер

## Як використовувати

### Варіант 1: Автоматичний деплой
```bash
cd /home/igor/developer/task.famhub.local
./scripts/deploy-viber-webhook.sh
```

### Варіант 2: Ручний деплой

#### Крок 1: Завантажити файли на сервер
```bash
# Через rsync (рекомендовано)
rsync -avz --exclude 'vendor' --exclude 'node_modules' --exclude '.git' \
  /home/igor/developer/task.famhub.local/ \
  user@server:/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/

# Або через scp (тільки змінені файли)
scp /home/igor/developer/task.famhub.local/routes/web.php \
  user@server:/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/routes/web.php

scp /home/igor/developer/task.famhub.local/bootstrap/app.php \
  user@server:/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/bootstrap/app.php
```

#### Крок 2: Налаштувати на сервері
```bash
ssh user@server
cd /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub

# Створити директорію логів
mkdir -p storage/logs
chmod -R 777 storage/logs

# Очистити кеш
php artisan optimize:clear

# Створити файл логу
touch storage/logs/viber_webhook.log
chmod 666 storage/logs/viber_webhook.log
```

#### Крок 3: Встановити webhook в Viber
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

#### Крок 4: Тестування
```bash
# Тест доступності
curl https://task.dev2025.ingsot.com/ping

# Тест webhook GET
curl https://task.dev2025.ingsot.com/viber/webhook

# Тест webhook POST (симуляція Viber)
curl -X POST https://task.dev2025.ingsot.com/viber/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "event": "message",
    "sender": {"id": "test123", "name": "Test User"},
    "message": {"type": "text", "text": "Test"}
  }'

# Перевірити логи на сервері
ssh user@server 'tail -100 /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/storage/logs/viber_webhook.log'
```

#### Крок 5: Реальний тест
1. Відкрийте бота в Viber
2. Відправте повідомлення
3. Перевірте логи:
```bash
ssh user@server 'tail -f /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/storage/logs/viber_webhook.log'
```

## Структура логів

Кожен запит до webhook логується з такою інформацією:

```json
{
    "timestamp": "2025-01-12 15:30:45",
    "method": "POST",
    "ip": "54.XXX.XXX.XXX",
    "headers": {
        "content-type": ["application/json"],
        "x-viber-content-type": ["application/json"],
        ...
    },
    "input": {
        "event": "message",
        "sender": {
            "id": "01234567890A=",
            "name": "User Name"
        },
        "message": {
            "type": "text",
            "text": "Повідомлення від користувача"
        }
    },
    "raw_content": "{\"event\":\"message\",..."
}
```

## Діагностика проблем

### Логи від curl є, від Viber - ні

**Можливі причини:**
1. Webhook не встановлений або встановлений неправильно
2. Viber не може підключитися до сервера (SSL, firewall, DNS)
3. IP адреса Viber заблокована

**Рішення:**
1. Перевірити webhook: `curl -X POST -H "X-Viber-Auth-Token: TOKEN" https://chatapi.viber.com/pa/get_account_info | jq '.webhook'`
2. Встановити webhook знову (див. Крок 3)
3. Перевірити SSL сертифікат: `curl -vI https://task.dev2025.ingsot.com 2>&1 | grep SSL`
4. Перевірити доступність ззовні: `curl https://task.dev2025.ingsot.com/ping` (з іншої машини)

### Помилка 500 Server Error

**Рішення:**
```bash
# На сервері:
php artisan optimize:clear
chmod -R 777 storage/logs
tail -100 storage/logs/laravel.log  # Подивитись детальну помилку
```

### Permission denied

**Рішення:**
```bash
# На сервері:
sudo chown -R www-data:www-data storage/logs  # для Apache
# або
sudo chown -R nginx:nginx storage/logs  # для Nginx
chmod -R 777 storage/logs
```

## Файли що були змінені

1. **routes/web.php** - основна логіка webhook
2. **bootstrap/app.php** - виключення CSRF для webhook
3. **docs/viber-webhook-debug.md** - документація з діагностики
4. **docs/viber-webhook-deployment.md** - документація з розгортання
5. **scripts/test-viber-webhook.sh** - скрипт тестування
6. **scripts/deploy-viber-webhook.sh** - скрипт деплою

## Корисні команди

```bash
# Моніторинг логів в реальному часі (на сервері)
tail -f storage/logs/viber_webhook.log

# Очистити логи
> storage/logs/viber_webhook.log

# Перевірити права доступу
ls -la storage/logs/

# Перевірити статус webhook в Viber
curl -X POST -H "X-Viber-Auth-Token: YOUR_TOKEN" -H "Content-Type: application/json" https://chatapi.viber.com/pa/get_account_info | jq '.'

# Видалити webhook з Viber
curl -X POST -H "X-Viber-Auth-Token: YOUR_TOKEN" -H "Content-Type: application/json" -d '{"url":""}' https://chatapi.viber.com/pa/set_webhook
```

## Важливо

- Після кожного оновлення коду виконуйте `php artisan optimize:clear` на сервері
- Переконайтесь що storage/logs має правильні права доступу (777)
- Viber вимагає валідний SSL сертифікат (не самопідписаний)
- Токен Viber зберігайте в безпеці

## Наступні кроки

Якщо після виконання всіх кроків логи від Viber все ще не з'являються:

1. Перевірте що webhook встановлений (`get_account_info`)
2. Перевірте SSL сертифікат (`curl -vI https://...`)
3. Перевірте доступність сервера ззовні
4. Зверніться до підтримки Viber з деталями проблеми

---

**Автор:** GitHub Copilot  
**Дата:** 2025-01-12  
**Версія:** 1.0

