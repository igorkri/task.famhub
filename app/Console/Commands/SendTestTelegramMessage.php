<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SendTestTelegramMessage extends Command
{
    protected $signature = 'telegram:send-test {message?}';

    protected $description = 'Отправить тестовое сообщение в Telegram';

    public function handle(): int
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            $this->error('❌ Telegram не настроен!');
            $this->info('Добавьте в .env:');
            $this->info('TELEGRAM_BOT_TOKEN=your_token');
            $this->info('TELEGRAM_CHAT_ID=your_chat_id');

            return Command::FAILURE;
        }

        $message = $this->argument('message') ?? $this->getDefaultMessage();

        $this->info('📤 Отправка сообщения в Telegram...');

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                $this->info('✅ Сообщение успешно отправлено!');
                $this->info("📱 Chat ID: {$chatId}");

                return Command::SUCCESS;
            } else {
                $this->error('❌ Ошибка отправки: '.$response->body());

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Исключение: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function getDefaultMessage(): string
    {
        return "🧪 <b>Тестовое сообщение</b>\n\n".
               '✅ Telegram бот настроен и работает!\n'.
               '📅 '.now()->format('d.m.Y H:i:s')."\n\n".
               '🔌 Система мониторинга отключений готова к работе.';
    }
}
