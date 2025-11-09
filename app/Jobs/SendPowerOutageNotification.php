<?php

namespace App\Jobs;

use App\Models\PowerOutageSchedule;
use App\Services\PowerOutageImageGenerator;
//use App\Services\PowerOutageImageGeneratorMobile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPowerOutageNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PowerOutageSchedule $schedule
    ) {}

    public function handle(): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            Log::warning('Telegram bot token or chat ID not configured');

            return;
        }

        try {
            // Генеруємо ДЕСКТОП зображення
            $imageGenerator = new PowerOutageImageGenerator;
            $imagePath = $imageGenerator->generate($this->schedule);

            // Формуємо caption (підпис до зображення)
            $caption = $this->formatCaption();

            // Відправляємо ДЕСКТОП фото в Telegram
            $response = Http::attach(
                'photo',
                file_get_contents($imagePath),
                'schedule.png'
            )->post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
                'chat_id' => $chatId,
                'caption' => $caption.' 🖥️ Десктоп версія',
                'parse_mode' => 'HTML',
            ]);

            // Видаляємо десктоп файл
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }

            // Генеруємо МОБІЛЬНУ версію
//            $mobileGenerator = new PowerOutageImageGeneratorMobile;
//            $mobileImagePath = $mobileGenerator->generate($this->schedule);

//            // Відправляємо МОБІЛЬНУ версію
//            $mobileResponse = Http::attach(
//                'photo',
//                file_get_contents($mobileImagePath),
//                'schedule_mobile.png'
//            )->post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
//                'chat_id' => $chatId,
//                'caption' => $caption.' 📱 Мобільна версія',
//                'parse_mode' => 'HTML',
//            ]);
//
//            // Видаляємо мобільний файл
//            if (file_exists($mobileImagePath)) {
//                unlink($mobileImagePath);
//            }

            if ($response->successful()) {
                Log::info('Power outage notification sent to Telegram', ['schedule_id' => $this->schedule->id]);
            } else {
                Log::error('Failed to send Telegram notification', [
                    'schedule_id' => $this->schedule->id,
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception sending Telegram notification', [
                'schedule_id' => $this->schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function formatCaption(): string
    {
        $date = $this->schedule->schedule_date->format('d.m.Y');
        $message = "🔌 <b>Графік відключень - {$date}</b>\n\n";

        // Добавляем информацию о периодах
        if (! empty($this->schedule->periods)) {
            $message .= "⏰ <b>Періоди:</b>\n";
            foreach ($this->schedule->periods as $period) {
                $message .= "• {$period['from']} - {$period['to']}: {$period['queues']} черг\n";
            }
        }

        return $message;
    }
}
