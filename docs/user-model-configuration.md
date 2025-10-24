# Модель User - Фінальна конфігурація

## ✅ Що налаштовано

### 1. Інтерфейси

Модель `User` реалізує два критично важливі інтерфейси:

```php
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
```

#### `FilamentUser`
- Дозволяє користувачу входити в Filament панель
- Вимагає метод `canAccessPanel(Panel $panel): bool`

#### `MustVerifyEmail`
- Активує верифікацію email
- Користувач повинен підтвердити email перед входом (якщо налаштовано)

### 2. Метод canAccessPanel

```php
public function canAccessPanel(Panel $panel): bool
{
    return true; // Дозволяє всім користувачам доступ
}
```

**Опції контролю доступу:**

```php
// Дозволити тільки адміністраторам
public function canAccessPanel(Panel $panel): bool
{
    return $this->hasRole('admin');
}

// Дозволити користувачам з верифікованим email
public function canAccessPanel(Panel $panel): bool
{
    return $this->hasVerifiedEmail();
}

// Комбінована логіка
public function canAccessPanel(Panel $panel): bool
{
    return $this->hasVerifiedEmail() && 
           $this->hasRole(['admin', 'manager']);
}

// Різні правила для різних панелей
public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'admin' => $this->hasRole('admin'),
        'app' => true,
        default => false,
    };
}
```

---

## Повний код модели User

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'asana_gid',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
```

---

## Перевірка налаштувань

### Через Tinker:

```bash
php artisan tinker
```

```php
use App\Models\User;

$user = User::first();

// Перевірити інтерфейси
$user instanceof \Filament\Models\Contracts\FilamentUser;     // має бути true
$user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail;  // має бути true

// Перевірити метод
method_exists($user, 'canAccessPanel');  // має бути true
$user->canAccessPanel(filament()->getPanel('admin'));  // має бути true

// Перевірити верифікацію
$user->hasVerifiedEmail();  // true, якщо email верифіковано
$user->email_verified_at;   // дата верифікації або null
```

---

## Функції, які тепер працюють

### ✅ Вхід в Filament
- Користувач може входити в панель адміністратора
- URL: `http://task.famhub.local/admin/login`

### ✅ Реєстрація (якщо активована)
- Користувачі можуть реєструватися
- URL: `http://task.famhub.local/admin/register`

### ✅ Відновлення пароля
- Забули пароль → отримати лист → скинути пароль
- URL: `http://task.famhub.local/admin/password-reset/request`

### ✅ Верифікація Email
- Після реєстрації користувач отримує лист для верифікації
- Клік по посиланню верифікує email

### ✅ Профіль користувача (якщо активований)
- Редагування інформації профілю
- Зміна пароля
- URL: `http://task.famhub.local/admin/profile`

---

## Маршрути аутентифікації

```bash
php artisan route:list --path=admin/
```

**Основні маршрути:**
- `admin/login` - Вхід
- `admin/logout` - Вихід
- `admin/password-reset/request` - Запит на скидання пароля
- `admin/password-reset/reset` - Скидання пароля
- `admin/email-verification/verify/{id}/{hash}` - Верифікація email

---

## Інтеграція з Spatie Permissions

Ваш `User` модель використовує `HasRoles` trait від Spatie, що дає можливість:

```php
// Перевірка ролей
$user->hasRole('admin');
$user->hasRole(['admin', 'manager']);
$user->hasAnyRole(['admin', 'manager']);

// Призначення ролей
$user->assignRole('admin');
$user->removeRole('admin');

// Перевірка дозволів
$user->can('edit tasks');
$user->hasPermissionTo('edit tasks');

// Використання в canAccessPanel
public function canAccessPanel(Panel $panel): bool
{
    return $this->hasRole('admin');
}
```

---

## Тестування

### Тест 1: Перевірка інтерфейсів

```php
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    public function test_user_implements_filament_user(): void
    {
        $user = User::factory()->create();
        
        $this->assertInstanceOf(
            \Filament\Models\Contracts\FilamentUser::class,
            $user
        );
    }
    
    public function test_user_can_access_panel(): void
    {
        $user = User::factory()->create();
        
        $this->assertTrue($user->canAccessPanel(
            filament()->getPanel('admin')
        ));
    }
}
```

### Тест 2: Перевірка аутентифікації

```php
public function test_user_can_login_to_filament(): void
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    
    $this->post('/admin/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertRedirect('/admin');
    
    $this->assertAuthenticatedAs($user);
}
```

---

## Налаштування верифікації Email

### Обов'язкова верифікація

В `AdminPanelProvider.php`:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->emailVerification()  // Обов'язкова верифікація
        // ...
}
```

### Необов'язкова верифікація

Видаліть `->emailVerification()` з панелі, але залишіть інтерфейс `MustVerifyEmail` на моделі. Користувачі зможуть входити без верифікації, але ви зможете перевіряти статус:

```php
if ($user->hasVerifiedEmail()) {
    // Додаткові можливості для верифікованих користувачів
}
```

---

## Поширені питання

### Q: Чому потрібен FilamentUser?
**A:** Це контракт Filament, який дозволяє контролювати доступ до панелі через метод `canAccessPanel()`.

### Q: Що робити, якщо користувач не може увійти?
**A:** Перевірте:
1. Чи повертає `canAccessPanel()` значення `true`
2. Чи правильний email та пароль
3. Чи активований обліковий запис

### Q: Як зробити обов'язкову верифікацію email?
**A:** Додайте `->emailVerification()` в `AdminPanelProvider` і переконайтеся, що модель реалізує `MustVerifyEmail`.

### Q: Чи можна мати різні правила для різних панелей?
**A:** Так! Використовуйте `$panel->getId()` в методі `canAccessPanel()`:

```php
public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'admin' => $this->hasRole('admin'),
        'app' => true,
        default => false,
    };
}
```

---

## Статус

✅ **FilamentUser** інтерфейс реалізовано  
✅ **MustVerifyEmail** інтерфейс реалізовано  
✅ **canAccessPanel()** метод додано  
✅ **Форматування коду** пройдено  
✅ **Тестування** успішне  

**Останнє оновлення:** 16 жовтня 2025  
**Версія:** Фінальна ✅
