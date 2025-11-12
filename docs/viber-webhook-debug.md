# Налагодження Viber Webhook

## Проблема
Через curl логи записуються, але від реального Viber бота логів немає.

## Можливі причини

### 1. **Viber не може підключитися до webhook URL**
- Переконайтеся що домен доступний ззовні
- Перевірте що SSL сертифікат валідний (Viber вимагає HTTPS)
- Viber не працює з самопідписаними сертифікатами

### 2. **Webhook неправильно налаштований в Viber**
Потрібно встановити webhook командою:
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

### 3. **Перевірка поточного webhook**
```bash
curl -X POST \
  -H "X-Viber-Auth-Token: 479d6bb020e7d3c0-10c469c78149798d-5cc4db7f99be936f" \
  -H "Content-Type: application/json" \
  https://chatapi.viber.com/pa/get_account_info
```

## Тестування

### Тест 1: Перевірка доступності сервера
```bash
curl https://task.dev2025.ingsot.com/ping
```
Очікувана відповідь: `{"status":"ok","time":"..."}`

### Тест 2: Перевірка GET запиту на webhook
```bash
curl https://task.dev2025.ingsot.com/viber/webhook
```
Очікувана відповідь: `{"status":0,"message":"Webhook endpoint is active"}`

### Тест 3: Перевірка POST запиту (симуляція Viber)
```bash
curl -X POST https://task.dev2025.ingsot.com/viber/webhook \
  -H "Content-Type: application/json" \
  -H "X-Viber-Content-Type: application/json" \
  -d '{
    "event": "message",
    "timestamp": 1699999999999,
    "message_token": 12345,
    "sender": {
      "id": "01234567890A=",
      "name": "Test User"
    },
    "message": {
      "type": "text",
      "text": "Тестове повідомлення"
    }
  }'
```

### Тест 4: Перевірка логів
```bash
cat /home/igor/developer/task.famhub.local/storage/logs/viber_webhook.log
```

## Файл логів

Всі запити логуються в:
```
/home/igor/developer/task.famhub.local/storage/logs/viber_webhook.log
```

Кожен запит містить:
- Timestamp
- HTTP method
- IP адресу відправника
- Всі headers
- Всі дані запиту (input)
- Raw content (тіло запиту)

## Що робити далі

1. **Запустіть тести 1-3** і перевірте чи вони працюють
2. **Перевірте логи** після кожного тесту
3. **Переконайтеся що webhook встановлений** в Viber (команда вище)
4. **Відправте повідомлення з Viber бота** і одразу перевірте логи
5. **Якщо логів немає** - проблема на стороні Viber (не може підключитися)
6. **Якщо логи є але відповіді немає** - проблема в коді обробки

## Структура запиту від Viber

Реальний запит від Viber виглядає так:
```json
{
  "event": "message",
  "timestamp": 1457764197627,
  "message_token": 4912661846655238145,
  "sender": {
    "id": "01234567890A=",
    "name": "John McClane",
    "avatar": "http://avatar.example.com",
    "language": "en",
    "country": "UK",
    "api_version": 1
  },
  "message": {
    "type": "text",
    "text": "a message to the service",
    "tracking_data": "tracking data"
  }
}
```

## Типи подій Viber

- `message` - нове повідомлення від користувача
- `subscribed` - користувач підписався на бота
- `unsubscribed` - користувач відписався від бота
- `conversation_started` - користувач відкрив чат з ботом
- `delivered` - повідомлення доставлено
- `seen` - повідомлення прочитано
- `failed` - помилка доставки

## Відладка

Якщо Viber не може підключитися, перевірте:

1. **DNS** - чи резолвиться домен?
```bash
nslookup task.dev2025.ingsot.com
```

2. **SSL** - чи валідний сертифікат?
```bash
curl -vI https://task.dev2025.ingsot.com 2>&1 | grep -i ssl
```

3. **Firewall** - чи дозволено вхідні з'єднання на 443 порт?

4. **Viber IP адреси** - можливо потрібно додати в whitelist
Viber використовує різні IP, тому білий список не рекомендується.

## Корисні команди

Очистити кеш роутів:
```bash
php artisan route:clear
php artisan cache:clear
php artisan config:clear
```

Переглянути всі роути:
```bash
php artisan route:list | grep viber
```

Моніторинг логів в реальному часі:
```bash
tail -f /home/igor/developer/task.famhub.local/storage/logs/viber_webhook.log
```

## Результат

Після виконання всіх кроків ви зможете:
1. Побачити чи доходять запити від Viber
2. Зрозуміти структуру даних що приходять
3. Налагодити обробку повідомлень

