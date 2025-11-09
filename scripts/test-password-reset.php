#!/usr/bin/env php
<?php

/**
 * Тестовий скрипт для відправки листа відновлення пароля
 *
 * Використання: php test-password-reset.php email@example.com
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Password;

// Отримуємо email з аргументів або використовуємо за замовчуванням
$email = $argv[1] ?? 'igorkri26@gmail.com';

echo "🔍 Пошук користувача з email: {$email}\n";

$user = User::where('email', $email)->first();

if (! $user) {
    echo "❌ Користувача з email {$email} не знайдено!\n";
    echo "\n📋 Доступні користувачі:\n";
    User::limit(5)->get()->each(function ($u) {
        echo "  - {$u->name} ({$u->email})\n";
    });
    exit(1);
}

echo "✅ Користувача знайдено: {$user->name}\n\n";

// Перевіряємо конфігурацію
echo "📧 Конфігурація пошти:\n";
echo '  Mailer: '.config('mail.default')."\n";
echo '  Host: '.config('mail.mailers.smtp.host')."\n";
echo '  Port: '.config('mail.mailers.smtp.port')."\n";
echo '  Encryption: '.config('mail.mailers.smtp.encryption')."\n";
echo '  From: '.config('mail.from.address')."\n\n";

// Відправляємо лист для відновлення пароля
echo "📨 Відправка листа для відновлення пароля...\n";

try {
    $status = Password::sendResetLink([
        'email' => $user->email,
    ]);

    if ($status === Password::RESET_LINK_SENT) {
        echo "✅ Лист для відновлення пароля відправлено успішно!\n";
        echo "\n📬 Перевірте пошту: {$user->email}\n";
        echo "\n💡 Якщо листа немає:\n";
        echo "   1. Перевірте папку \"Спам\"\n";
        echo "   2. Перевірте логи: tail -f storage/logs/laravel.log\n";
        echo "   3. Перевірте конфігурацію .env\n";
    } else {
        echo "❌ Помилка: {$status}\n";
    }
} catch (\Exception $e) {
    echo '❌ Виняток: '.$e->getMessage()."\n";
    echo '   Файл: '.$e->getFile().':'.$e->getLine()."\n";
}

echo "\n";
