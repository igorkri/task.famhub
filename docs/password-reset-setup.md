# Настройка восстановления пароля и почты в Filament

## Что было настроено

### 1. Включена функция восстановления пароля в Filament

В файле `app/Providers/Filament/AdminPanelProvider.php` добавлены методы:
- `->passwordReset()` - включает функцию восстановления пароля
- `->emailVerification()` - включает верификацию email

Теперь на странице входа будет отображаться ссылка "Забыли пароль?".

### 2. Модель User обновлена

Модель `app/Models/User.php` теперь реализует интерфейс `MustVerifyEmail`, что позволяет использовать функцию верификации email.

### 3. Настройка почты

Необходимо обновить настройки почты в файле `.env`:

#### Для Gmail:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=ваш-email@gmail.com
MAIL_PASSWORD=ваш-app-пароль
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@famhub.local"
MAIL_FROM_NAME="${APP_NAME}"
```

**Важно для Gmail**: Необходимо создать App Password (пароль приложения):
1. Перейдите на https://myaccount.google.com/security
2. Включите двухфакторную аутентификацию (если еще не включена)
3. Перейдите в "Пароли приложений" (App Passwords)
4. Создайте новый пароль для "Почта"
5. Используйте этот пароль в `MAIL_PASSWORD`

#### Для Mailtrap (для тестирования):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=ваш-mailtrap-username
MAIL_PASSWORD=ваш-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@famhub.local"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Для других почтовых сервисов:

**Mailgun:**
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=ваш-домен.mailgun.org
MAILGUN_SECRET=ваш-api-ключ
```

**Amazon SES:**
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=ваш-access-key
AWS_SECRET_ACCESS_KEY=ваш-secret-key
AWS_DEFAULT_REGION=us-east-1
```

## Как использовать

### Восстановление пароля:

1. Откройте страницу входа: `http://task.famhub.local/admin/login`
2. Нажмите на ссылку "Забыли пароль?"
3. Введите email адрес
4. Проверьте почту и перейдите по ссылке
5. Введите новый пароль

### Тестирование отправки почты:

Вы можете протестировать отправку почты с помощью Tinker:

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

// Отправить тестовое письмо
Mail::raw('Тестовое письмо', function ($message) {
    $message->to('test@example.com')->subject('Тест');
});

// Отправить письмо восстановления пароля
$user = \App\Models\User::first();
Password::sendResetLink(['email' => $user->email]);
```

## Проверка конфигурации

### Проверить подключение к почтовому серверу:

```bash
php artisan tinker
```

```php
try {
    Mail::raw('Test', function($message) {
        $message->to('test@example.com')->subject('Test');
    });
    echo "Почта работает!";
} catch (\Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}
```

### Просмотр логов почты:

Если используется драйвер `log` (для разработки):

```bash
tail -f storage/logs/laravel.log
```

## Настройка шаблонов писем (опционально)

Если вы хотите кастомизировать шаблоны писем, выполните:

```bash
php artisan vendor:publish --tag=laravel-mail
```

Шаблоны будут доступны в `resources/views/vendor/mail`.

Для кастомизации уведомлений о восстановлении пароля:

```bash
php artisan vendor:publish --tag=laravel-notifications
```

## Безопасность

- Убедитесь, что переменные `MAIL_USERNAME` и `MAIL_PASSWORD` не попадают в систему контроля версий
- Используйте App Passwords для Gmail, а не обычный пароль
- Настройте правильный `MAIL_FROM_ADDRESS` для вашего домена
- Для production используйте профессиональные почтовые сервисы (Mailgun, SES, SendGrid)

## Дополнительные настройки

### Изменить время действия токена восстановления пароля:

В `config/auth.php`:

```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60, // минуты (по умолчанию 60)
        'throttle' => 60, // секунды между запросами
    ],
],
```

### Отключить верификацию email:

Если не нужна верификация email, удалите `->emailVerification()` из `AdminPanelProvider.php`.

### Кастомизация страницы восстановления пароля:

Вы можете создать собственную страницу, расширив базовую:

```bash
php artisan make:filament-page Auth/RequestPasswordReset
```

## Возможные проблемы

### Письма не отправляются:

1. Проверьте настройки `.env`
2. Проверьте логи: `storage/logs/laravel.log`
3. Убедитесь, что порт не заблокирован firewall
4. Проверьте правильность учетных данных

### Ошибка "Connection could not be established":

- Проверьте `MAIL_HOST` и `MAIL_PORT`
- Убедитесь, что сервер доступен
- Попробуйте использовать Mailtrap для тестирования

### Письма попадают в спам:

- Настройте SPF, DKIM и DMARC записи для вашего домена
- Используйте профессиональный почтовый сервис
- Убедитесь, что `MAIL_FROM_ADDRESS` соответствует вашему домену
