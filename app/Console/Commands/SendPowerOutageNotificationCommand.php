<?php

namespace App\Console\Commands;

use App\Jobs\SendPowerOutageNotification;
use App\Models\PowerOutageSchedule;
use Illuminate\Console\Command;

class SendPowerOutageNotificationCommand extends Command
{
    protected $signature = 'power:notify {date?}';

    protected $description = 'Отправить уведомление о графике отключений в Telegram';

    public function handle(): int
    {
        $date = $this->argument('date');

        if ($date) {
            // Получаем график на конкретную дату
            $scheduleDate = now()->createFromFormat('d-m-Y', $date)->format('Y-m-d');
            $schedule = PowerOutageSchedule::whereDate('schedule_date', $scheduleDate)
                ->latest('fetched_at')
                ->first();

            if (! $schedule) {
                $this->error("График на {$date} не найден в базе данных");
                $this->info('Сначала получите график: php artisan power:fetch-schedule '.$date);

                return Command::FAILURE;
            }
        } else {
            // Получаем последний график
            $schedule = PowerOutageSchedule::latest('fetched_at')->first();

            if (! $schedule) {
                $this->error('В базе данных нет графиков');
                $this->info('Сначала получите график: php artisan power:fetch-schedule');

                return Command::FAILURE;
            }
        }

        $this->info('📤 Отправка уведомления о графике...');
        $this->info('📅 Дата: '.$schedule->schedule_date->format('d.m.Y'));
        $this->info('🕒 Получен: '.$schedule->fetched_at->format('d.m.Y H:i:s'));

        SendPowerOutageNotification::dispatchSync($schedule);

        $this->info('✅ Уведомление отправлено в Telegram!');

        return Command::SUCCESS;
    }
}

