# Діагностика проблем з відправкою пошти

## ✅ Проблему вирішено!

### Що було не так:

У файлі `.env` була вказана неправильна адреса відправника:
```env
MAIL_FROM_ADDRESS="noreply@famhub.local"  ❌ НЕПРАВИЛЬНО
```

Gmail не дозволяє відправляти листи з довільних адрес. Потрібно використовувати ту саму адресу, що і в `MAIL_USERNAME`.

### Що виправили:

```env
MAIL_FROM_ADDRESS="dev.masterok@gmail.com"  ✅ ПРАВИЛЬНО
```

---

## Як перевірити, чи працює пошта

### 1. Перевірка конфігурації

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Config;

echo "Mailer: " . Config::get('mail.default') . "\n";
echo "Host: " . Config::get('mail.mailers.smtp.host') . "\n";
echo "Port: " . Config::get('mail.mailers.smtp.port') . "\n";
echo "From: " . Config::get('mail.from.address') . "\n";
```

### 2. Тестова відправка листа

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Тестовий лист', function($message) {
    $message->to('your-email@gmail.com')
            ->subject('Тест пошти');
});

echo "Лист відправлено!\n";
```

### 3. Перевірка логів

Якщо щось не працює, перевірте логи:

```bash
tail -f storage/logs/laravel.log
```

---

## Типові проблеми та рішення

### Проблема 1: Листи не приходять

**Причини:**
- Неправильний `MAIL_FROM_ADDRESS`
- Неправильний пароль додатку Gmail
- Заблокований порт 587

**Рішення:**
1. Перевірте, що `MAIL_FROM_ADDRESS` = `MAIL_USERNAME`
2. Переконайтеся, що використовуєте пароль додатку (App Password), а не звичайний пароль
3. Спробуйте порт 465 з `MAIL_ENCRYPTION=ssl`

### Проблема 2: Листи потрапляють у спам

**Причини:**
- Використовується неправильна адреса відправника
- Відсутні SPF/DKIM записи

**Рішення:**
1. Використовуйте ту саму адресу, що й в `MAIL_USERNAME`
2. Для власного домену налаштуйте SPF, DKIM, DMARC записи
3. Використовуйте професійний сервіс (Mailgun, Amazon SES, SendGrid)

### Проблема 3: Помилка "Authentication failed"

**Причини:**
- Неправильний пароль
- Не створено пароль додатку
- Не увімкнена двофакторна автентифікація

**Рішення:**
1. Перейдіть на https://myaccount.google.com/security
2. Увімкніть двофакторну автентифікацію
3. Створіть пароль додатку (App Password)
4. Використовуйте цей пароль у `MAIL_PASSWORD`

### Проблема 4: "Connection refused" або "Connection timeout"

**Причини:**
- Заблокований порт
- Неправильний хост
- Проблеми з firewall

**Рішення:**
```env
# Спробуйте альтернативні налаштування
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

---

## Перевірка відновлення пароля

### Спосіб 1: Через інтерфейс

1. Вийдіть з системи
2. Перейдіть на http://task.famhub.local/admin/login
3. Натисніть "Forgot your password?"
4. Введіть email
5. Перевірте пошту

### Спосіб 2: Через Tinker

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Password;
use App\Models\User;

$user = User::where('email', 'your-email@gmail.com')->first();

if ($user) {
    Password::broker('users')->sendResetLink([
        'email' => $user->email
    ]);
    echo "Лист для відновлення пароля відправлено!\n";
} else {
    echo "Користувача не знайдено\n";
}
```

---

## Налаштування для різних поштових сервісів

### Gmail (поточна конфігурація)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=dev.masterok@gmail.com
MAIL_PASSWORD='your-app-password'
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="dev.masterok@gmail.com"
MAIL_FROM_NAME="TaskManager"
```

### Mailgun (рекомендується для production)

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.mailgun.org
MAILGUN_SECRET=your-api-key
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="TaskManager"
```

### Amazon SES

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="TaskManager"
```

### Mailtrap (для тестування)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="test@example.com"
MAIL_FROM_NAME="TaskManager"
```

---

## Корисні команди

### Очистити кеш після змін у .env:
```bash
php artisan config:clear
php artisan cache:clear
```

### Переглянути черги (якщо використовуються):
```bash
php artisan queue:work
```

### Переглянути маршрути пошти:
```bash
php artisan route:list --path=admin/password
php artisan route:list --path=admin/email
```

---

## Статус системи

✅ Пошта налаштована і працює  
✅ Відправка листів працює  
✅ Gmail SMTP підключено  
✅ Відновлення пароля активовано  
✅ Верифікація email активована  

## Що відправляється

- ✅ Листи верифікації email
- ✅ Листи відновлення пароля
- ✅ Тестові листи

Всі листи тепер відправляються з правильної адреси `dev.masterok@gmail.com` і мають дійсний домен.
