# Налаштування відновлення пароля та пошти в Filament

## Що було налаштовано

✅ Функція відновлення пароля в Filament
✅ Верифікація email
✅ Модель User оновлена для підтримки верифікації
✅ Базова конфігурація пошти

## Швидке налаштування пошти

### 1. Відредагуйте файл `.env`:

**Для Gmail:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=ваш-email@gmail.com
MAIL_PASSWORD=пароль-додатку-gmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@famhub.local"
MAIL_FROM_NAME="TaskManager"
```

**Для тестування (Mailtrap):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=ваш-username-з-mailtrap
MAIL_PASSWORD=ваш-password-з-mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@famhub.local"
MAIL_FROM_NAME="TaskManager"
```

### 2. Для Gmail створіть пароль додатку:

1. Перейдіть: https://myaccount.google.com/security
2. Увімкніть двофакторну автентифікацію
3. Знайдіть "Паролі додатків" (App Passwords)
4. Створіть новий пароль для "Пошта"
5. Використовуйте цей пароль у `MAIL_PASSWORD`

### 3. Очистіть кеш:

```bash
php artisan config:clear
php artisan cache:clear
```

## Як користуватися

### Відновлення пароля:

1. Відкрийте: `http://task.famhub.local/admin/login`
2. Натисніть "Забули пароль?"
3. Введіть email
4. Перевірте пошту
5. Перейдіть за посиланням та введіть новий пароль

### Тестування пошти:

```bash
php artisan tinker
```

```php
Mail::raw('Тест', function ($message) {
    $message->to('test@example.com')->subject('Тестовий лист');
});
```

## Налаштування для різних сервісів

### Mailgun:
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=ваш-домен.mailgun.org
MAILGUN_SECRET=ваш-api-ключ
MAIL_FROM_ADDRESS="noreply@famhub.local"
```

### Amazon SES:
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=ваш-ключ
AWS_SECRET_ACCESS_KEY=ваш-секрет
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS="noreply@famhub.local"
```

## Перевірка роботи

### Тест підключення:

```bash
php artisan tinker
```

```php
try {
    Mail::raw('Test', function($message) {
        $message->to('test@example.com')->subject('Test');
    });
    echo "Пошта працює!";
} catch (\Exception $e) {
    echo "Помилка: " . $e->getMessage();
}
```

### Перегляд логів:

```bash
tail -f storage/logs/laravel.log
```

## Можливі проблеми

### Листи не відправляються:
- Перевірте `.env`
- Перегляньте `storage/logs/laravel.log`
- Перевірте правильність облікових даних
- Переконайтеся, що порт не заблокований

### Gmail блокує вхід:
- Використовуйте пароль додатку (App Password), не звичайний пароль
- Увімкніть двофакторну автентифікацію

### Листи потрапляють у спам:
- Використовуйте професійний поштовий сервіс (Mailgun, SES, SendGrid)
- Налаштуйте SPF, DKIM записи

## Додаткова інформація

Детальна документація: `docs/password-reset-setup.md`

## Зміни в коді

### AdminPanelProvider.php
Додано:
```php
->passwordReset()      // Відновлення пароля
->emailVerification()  // Верифікація email
```

### User.php
Додано інтерфейс:
```php
class User extends Authenticatable implements MustVerifyEmail
```

Тепер все готово до використання! 🎉
