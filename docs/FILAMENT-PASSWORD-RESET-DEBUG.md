# Тестування відновлення пароля через форму Filament

## Крок за кроком

### 1. Відкрийте сторінку входу
```
http://task.famhub.local/admin/login
```

### 2. Натисніть "Forgot your password?"

### 3. Введіть email
```
igorkri26@gmail.com
```

### 4. Натисніть кнопку відправки

### 5. Перевірте логи Laravel
```bash
tail -f storage/logs/laravel.log
```

### 6. Перевірте пошту
- Основна папка
- Папка "Спам"

---

## Якщо не працює через форму

### Варіант 1: Перевірити через Tinker

```bash
php artisan tinker
```

```php
use App\Models\User;

$user = User::where('email', 'igorkri26@gmail.com')->first();
$user->sendPasswordResetNotification(
    app('auth.password.broker')->createToken($user)
);

echo "Лист відправлено!\n";
```

### Варіант 2: Перевірити через тестовий скрипт

```bash
php test-password-reset.php igorkri26@gmail.com
```

### Варіант 3: Ручний URL

Згенеруйте URL через Tinker:

```php
use App\Models\User;

$user = User::where('email', 'igorkri26@gmail.com')->first();
$token = app('auth.password.broker')->createToken($user);
$url = route('filament.admin.auth.password-reset.reset', [
    'token' => $token,
    'email' => $user->email,
]);

echo "URL: " . $url . "\n";
```

Потім відкрийте цей URL у браузері.

---

## Перевірка конфігурації Filament

### 1. Перевірити, що панель налаштована:

```bash
php artisan route:list --path=admin/password
```

**Очікуваний результат:**
```
admin/password-reset/request  
admin/password-reset/reset
```

### 2. Перевірити AdminPanelProvider:

```php
// app/Providers/Filament/AdminPanelProvider.php

return $panel
    ->passwordReset()           // ✅ Має бути
    ->authPasswordBroker('users')  // ✅ Має бути
```

### 3. Перевірити модель User:

```php
// app/Models/User.php

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
```

---

## Можливі проблеми

### Проблема 1: Помилка "Route not defined"

**Рішення:**
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### Проблема 2: Throttling (занадто багато спроб)

**Рішення:** Зачекайте 60 секунд або очистіть таблицю:

```bash
php artisan tinker
```

```php
DB::table('password_reset_tokens')->delete();
```

### Проблема 3: Лист не відправляється

**Діагностика:**

1. Перевірити логи:
```bash
tail -f storage/logs/laravel.log
```

2. Перевірити конфігурацію:
```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Config;

echo "Mailer: " . Config::get('mail.default') . "\n";
echo "Host: " . Config::get('mail.mailers.smtp.host') . "\n";
echo "Encryption: " . Config::get('mail.mailers.smtp.encryption') . "\n";
echo "From: " . Config::get('mail.from.address') . "\n";
```

**Має бути:**
- Mailer: smtp
- Host: smtp.gmail.com
- Encryption: tls (НЕ null!)
- From: dev.masterok@gmail.com

---

## Налагодження через логи

### Увімкнути детальне логування:

У файлі `.env`:
```env
LOG_LEVEL=debug
```

Потім перезапустити:
```bash
php artisan config:clear
```

### Переглядати логи в реальному часі:

```bash
tail -f storage/logs/laravel.log | grep -i "mail\|password"
```

---

## Тестування з різними користувачами

```bash
# Знайти всіх користувачів
php artisan tinker
```

```php
use App\Models\User;

User::select('id', 'name', 'email')->get();
```

Потім протестувати з будь-яким email:

```bash
php test-password-reset.php EMAIL_КОРИСТУВАЧА
```

---

## Якщо все ще не працює

### Перевірити, чи Filament використовує правильний broker:

```bash
php artisan tinker
```

```php
use Filament\Facades\Filament;

$panel = Filament::getPanel('admin');
echo "Password Broker: " . $panel->getAuthPasswordBroker() . "\n";
```

**Має бути:** `users`

### Перевірити конфігурацію auth:

```bash
php artisan tinker
```

```php
Config::get('auth.passwords.users');
```

**Має бути:**
```php
[
    'provider' => 'users',
    'table' => 'password_reset_tokens',
    'expire' => 60,
    'throttle' => 60,
]
```

---

## Фінальна перевірка

Виконайте всі кроки:

1. ✅ Очистити кеш
```bash
php artisan optimize:clear
```

2. ✅ Перевірити конфігурацію
```bash
php test-password-reset.php igorkri26@gmail.com
```

3. ✅ Відкрити форму у браузері
```
http://task.famhub.local/admin/password-reset/request
```

4. ✅ Ввести email і відправити

5. ✅ Перевірити логи
```bash
tail -f storage/logs/laravel.log
```

6. ✅ Перевірити пошту

---

## Контакт для допомоги

Якщо нічого не допомагає, збер файл:
- `storage/logs/laravel.log` (останні 100 рядків)
- `.env` (без паролів!)
- `config/mail.php`
- `app/Models/User.php`
- `app/Providers/Filament/AdminPanelProvider.php`

І поділіться ними для детального аналізу.
