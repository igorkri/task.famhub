<?php

namespace App\Jobs;

use App\Models\PowerOutageSchedule;
use App\Services\PowerOutageImageGenerator;
// use App\Services\PowerOutageImageGeneratorMobile;
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
                'caption' => $caption,
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
        $dayOfWeek = $this->schedule->schedule_date->translatedFormat('l'); // День тижня

        // Визначаємо тип оновлення
        $isToday = $this->schedule->schedule_date->isToday();
        $isTomorrow = $this->schedule->schedule_date->isTomorrow();
        $isUpdate = PowerOutageSchedule::where('schedule_date', $this->schedule->schedule_date)
            ->where('id', '!=', $this->schedule->id)
            ->exists();

        // Заголовок залежно від типу
        if ($isToday && $isUpdate) {
            $message = "🔄 <b>ОНОВЛЕННЯ графіка на СЬОГОДНІ</b>\n";
            $message .= "📅 {$date} ({$dayOfWeek})\n\n";
            $message .= "⚠️ <i>Графік відключень змінився!</i>\n\n";
        } elseif ($isToday) {
            $message = "🔌 <b>Графік відключень на СЬОГОДНІ</b>\n";
            $message .= "📅 {$date} ({$dayOfWeek})\n\n";
        } elseif ($isTomorrow) {
            $message = "📅 <b>НОВИЙ графік на ЗАВТРА</b>\n";
            $message .= "🗓 {$date} ({$dayOfWeek})\n\n";
            $message .= "✨ <i>Графік на завтра опубліковано!</i>\n\n";
        } else {
            $message = "🔌 <b>Графік відключень</b>\n";
            $message .= "📅 {$date} ({$dayOfWeek})\n\n";
        }

        // Інформація про періоди відключень
//        if (! empty($this->schedule->periods)) {
//            $message .= "⏰ <b>Періоди відключень:</b>\n";
//            foreach ($this->schedule->periods as $period) {
//                $duration = $this->calculateDuration($period['from'], $period['to']);
//                $message .= "• {$period['from']} - {$period['to']} ({$duration})\n";
//                $message .= "  └ Черги: <b>{$period['queues']}</b>\n";
//            }
//            $message .= "\n";
//        }

        // Додаткова інформація з metadata
//        if (! empty($this->schedule['fetched_at'])) {
//            $message .= "📝 <i>Опубліковано: {$this->schedule->fetched_at->format('d.m.Y H:i')}</i>\n";
//        }

//        // Статистика по чергах
//        $queueStats = $this->getQueueStatistics();
//        if (! empty($queueStats)) {
//            $message .= "\n📊 <b>Статистика по чергах:</b>\n";
//            foreach ($queueStats as $queue => $stats) {
//                $hoursOff = round($stats['hours_off'], 1);
//                $message .= "• Черга {$queue}: <b>{$hoursOff} год</b> без світла\n";
//            }
//        }

        // Опис з ДТЕК (якщо є)
//        if (! empty($this->schedule->description)) {
//            $shortDescription = mb_substr($this->schedule->description, 0, 550);
//            if (mb_strlen($this->schedule->description) > 550) {
//                $shortDescription .= '...';
//            }
//            $message .= "\n💬 {$shortDescription}\n";
//        }

        return $message;
    }

    /**
     * Розрахунок тривалості періоду
     */
    protected function calculateDuration(string $from, string $to): string
    {
        try {
            $start = \Carbon\Carbon::createFromFormat('H:i', $from);
            $end = \Carbon\Carbon::createFromFormat('H:i', $to);

            if ($end->lessThan($start)) {
                $end->addDay();
            }

            $diff = $start->diff($end);
            $hours = $diff->h;
            $minutes = $diff->i;

            if ($minutes > 0) {
                return "{$hours} год {$minutes} хв";
            }

            return "{$hours} год";
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Отримання статистики по чергах
     */
    protected function getQueueStatistics(): array
    {
        if (empty($this->schedule->schedule_data)) {
            return [];
        }

        $stats = [];

        foreach ($this->schedule->schedule_data as $queueData) {
            $queueNumber = $queueData['subqueue'] ?? $queueData['queue'] ?? 'unknown';
            $hourlyStatus = $queueData['hourly_status'] ?? [];

            // Підраховуємо години без світла
            $hoursOff = 0;
            foreach ($hourlyStatus as $status) {
                if ($status === 'off') {
                    $hoursOff += 0.5; // Кожен елемент = 30 хвилин
                }
            }

            if ($hoursOff > 0) {
                $stats[$queueNumber] = [
                    'hours_off' => $hoursOff,
                ];
            }
        }

        // Сортуємо по номеру черги
        ksort($stats);

        return $stats;
    }
}
