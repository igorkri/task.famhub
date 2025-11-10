# HTTP заголовки для імітації браузера користувача

## Чому це важливо

При автоматичних запитах до веб-сайтів важливо імітувати звичайний браузер користувача, щоб:
- Уникнути блокування за підозрою в bot-активності
- Отримувати ті самі дані, що й користувач у браузері
- Зменшити ймовірність отримання помилок 403 Forbidden або 429 Too Many Requests

## Використовувані заголовки

### User-Agent
```
Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36
```
**Призначення:** Ідентифікація браузера. Використовується актуальна версія Chrome на Windows 10.

### Accept
```
text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8
```
**Призначення:** Вказує, які типи контенту приймає клієнт.

### Accept-Language
```
uk-UA,uk;q=0.9,ru;q=0.8,en;q=0.7
```
**Призначення:** Пріоритет мов. Українська -> Російська -> Англійська (релевантно для України).

### Accept-Encoding
```
gzip, deflate, br
```
**Призначення:** Підтримка стиснення даних (gzip, deflate, Brotli).

### Referer
```
https://www.poe.pl.ua/
```
**Призначення:** Показує, що запит йде з головної сторінки сайту (користувач перейшов з головної).

### Origin
```
https://www.poe.pl.ua
```
**Призначення:** Джерело запиту (для CORS).

### Connection
```
keep-alive
```
**Призначення:** Підтримка постійного з'єднання.

### Security Headers (Sec-Fetch-*)
```
Sec-Fetch-Dest: document
Sec-Fetch-Mode: navigate
Sec-Fetch-Site: same-origin
Sec-Fetch-User: ?1
```
**Призначення:** Заголовки безпеки Chrome/Chromium для визначення типу запиту.

### Upgrade-Insecure-Requests
```
1
```
**Призначення:** Браузер може оновити HTTP до HTTPS.

### Cache-Control
```
max-age=0
```
**Призначення:** Запит свіжих даних, не з кешу.

## Код імплементації

```php
$response = Http::asForm()
    ->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language' => 'uk-UA,uk;q=0.9,ru;q=0.8,en;q=0.7',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Referer' => 'https://www.poe.pl.ua/',
        'Origin' => 'https://www.poe.pl.ua',
        'Connection' => 'keep-alive',
        'Sec-Fetch-Dest' => 'document',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Site' => 'same-origin',
        'Sec-Fetch-User' => '?1',
        'Upgrade-Insecure-Requests' => '1',
        'Cache-Control' => 'max-age=0',
    ])
    ->timeout(30)
    ->retry(3, 100)
    ->post('https://www.poe.pl.ua/customs/newgpv-info.php', [
        'seldate' => json_encode(['date_in' => $date]),
    ]);
```

## Додаткові міри безпеки

### Timeout і Retry
```php
->timeout(30)      // 30 секунд на запит
->retry(3, 100)    // 3 спроби з паузою 100ms між ними
```

### Інтервал між запитами
Scheduler налаштовано на запуск кожні 3 хвилини, що є прийнятним інтервалом і не створює надмірного навантаження на сервер.

## Як оновити User-Agent

Якщо з часом потрібно оновити User-Agent на актуальнішу версію:

1. Відкрийте браузер Chrome
2. Перейдіть на сторінку: `chrome://version/`
3. Скопіюйте рядок **User Agent**
4. Оновіть у коді

Або використайте онлайн-сервіс: https://www.whatismybrowser.com/detect/what-is-my-user-agent/

## Альтернативні User-Agent'и

### Chrome (Windows)
```
Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36
```

### Firefox (Windows)
```
Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0
```

### Safari (macOS)
```
Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15
```

### Edge (Windows)
```
Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0
```

## Моніторинг

Якщо сервер починає блокувати запити:

1. Перевірте логи Laravel: `tail -f storage/logs/laravel.log`
2. Спробуйте змінити User-Agent
3. Збільште інтервал між запитами
4. Перевірте, чи змінилася структура API на сайті ДТЕК

## Тестування

Для тестування, що заголовки працюють:

```bash
php artisan power:fetch-schedule -v
```

Якщо отримуєте дані без помилок - все працює правильно.

## Посилання

- Файл: `app/Console/Commands/FetchPowerOutageSchedule.php`
- Документація Laravel HTTP: https://laravel.com/docs/11.x/http-client
- Scheduler: `routes/console.php`

---

**Останнє оновлення:** 10.11.2025  
**Статус:** ✅ Працює стабільно

