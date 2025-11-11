<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendAirAlertNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $region,
        public bool $isActive,
        public ?string $additionalInfo = null
    ) {}

    public function handle(TelegramService $telegram): void
    {
        try {
            $message = $this->formatMessage();

            $telegram->sendMessage(
                message: $message,
                sendToDev: true
            );

        } catch (\Exception $e) {
            Log::error('Exception sending air alert notification', [
                'region' => $this->region,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function formatMessage(): string
    {
        if ($this->isActive) {
            $message = "🚨 <b>ПОВІТРЯНА ТРИВОГА!</b>\n\n";
            $message .= "📍 Регіон: <b>{$this->region}</b>\n";
            $message .= "⚠️ <i>Пройдіть до укриття!</i>\n";
        } else {
            $message = "✅ <b>Відбій повітряної тривоги</b>\n\n";
            $message .= "📍 Регіон: <b>{$this->region}</b>\n";
        }

        if ($this->additionalInfo) {
            $message .= "\n💬 {$this->additionalInfo}\n";
        }

        return $message;
    }
}
