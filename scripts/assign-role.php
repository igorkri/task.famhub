#!/usr/bin/env php
<?php

/**
 * Скрипт для призначення ролі користувачу
 *
 * Використання:
 *   php assign-role.php EMAIL ROLE_NAME
 *
 * Приклади:
 *   php assign-role.php user@example.com super_admin
 *   php assign-role.php user@example.com panel_user
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

// Перевірка аргументів
if ($argc < 3) {
    echo "❌ Помилка: Недостатньо аргументів\n\n";
    echo "Використання:\n";
    echo "  php assign-role.php EMAIL ROLE_NAME\n\n";
    echo "Приклади:\n";
    echo "  php assign-role.php user@example.com super_admin\n";
    echo "  php assign-role.php user@example.com panel_user\n\n";
    echo "Доступні ролі:\n";
    $roles = Role::pluck('name')->toArray();
    foreach ($roles as $role) {
        echo "  - {$role}\n";
    }
    exit(1);
}

$email = $argv[1];
$roleName = $argv[2];

try {
    // Знайти користувача
    $user = User::where('email', $email)->first();

    if (! $user) {
        echo "❌ Користувача з email '{$email}' не знайдено\n";
        echo "\nДоступні користувачі:\n";
        $users = User::select('id', 'name', 'email')->get();
        foreach ($users as $u) {
            echo "  - {$u->email} ({$u->name})\n";
        }
        exit(1);
    }

    // Перевірити, чи існує роль
    $role = Role::where('name', $roleName)->first();

    if (! $role) {
        echo "❌ Роль '{$roleName}' не знайдено\n";
        echo "\nДоступні ролі:\n";
        $roles = Role::all();
        foreach ($roles as $r) {
            echo "  - {$r->name}\n";
        }
        exit(1);
    }

    // Призначити роль
    $user->assignRole($roleName);

    echo "✅ Успішно!\n\n";
    echo "Користувач: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Призначена роль: {$roleName}\n\n";

    echo "Всі ролі користувача:\n";
    foreach ($user->roles as $userRole) {
        echo "  - {$userRole->name}\n";
    }

    // Скинути кеш прав
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    echo "\n✅ Кеш прав скинуто\n";

} catch (Exception $e) {
    echo '❌ Помилка: '.$e->getMessage()."\n";
    exit(1);
}
