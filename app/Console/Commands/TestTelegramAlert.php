<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TestTelegramAlert extends Command
{
    protected $signature = 'telegram:test-alert {--alert : Тестове повідомлення про тривогу} {--clear : Тестове повідомлення про відбій}';

    protected $description = 'Відправити тестове повідомлення про тривогу/відбій в Telegram';

    public function handle(TelegramService $telegram): int
    {
        $this->info('📱 Відправка тестового повідомлення в Telegram...');
        $this->newLine();

        if ($this->option('alert')) {
            return $this->sendAlertTest($telegram);
        }

        if ($this->option('clear')) {
            return $this->sendClearTest($telegram);
        }

        // За замовчуванням - обидва повідомлення
        $this->sendAlertTest($telegram);
        $this->newLine();
        sleep(2);
        $this->sendClearTest($telegram);

        return Command::SUCCESS;
    }

    protected function sendAlertTest(TelegramService $telegram): int
    {
        $message = "🧪 <b>ТЕСТОВА ПОВІТРЯНА ТРИВОГА!</b>\n\n";
        $message .= "📍 Регіон: <b>Полтавська область</b>\n";
        $message .= "⚠️ <i>Це тест системи сповіщень</i>\n\n";
        $message .= '⏰ '.now()->format('H:i:s d.m.Y');

        if ($telegram->sendMessage($message, sendToDev: true)) {
            $this->info('✅ Тестове повідомлення про ТРИВОГУ відправлено!');
            $this->line('   Перевірте Telegram');

            return Command::SUCCESS;
        }

        $this->error('❌ Помилка відправки повідомлення');

        return Command::FAILURE;
    }

    protected function sendClearTest(TelegramService $telegram): int
    {
        $message = "🧪 <b>ТЕСТОВИЙ ВІДБІЙ ТРИВОГИ</b>\n\n";
        $message .= "📍 Регіон: <b>Полтавська область</b>\n";
        $message .= "✅ <i>Це тест системи сповіщень</i>\n\n";
        $message .= '⏰ '.now()->format('H:i:s d.m.Y');

        if ($telegram->sendMessage($message, sendToDev: true)) {
            $this->info('✅ Тестове повідомлення про ВІДБІЙ відправлено!');
            $this->line('   Перевірте Telegram');

            return Command::SUCCESS;
        }

        $this->error('❌ Помилка відправки повідомлення');

        return Command::FAILURE;
    }
}
