<?php

namespace App\Console\Commands;

use App\Models\AirAlert;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class AirAlertDailyReport extends Command
{
    protected $signature = 'air-alert:daily-report {--region= : ID регіону (необов\'язково)}';

    protected $description = 'Щоденний звіт про повітряні тривоги';

    public function handle(TelegramService $telegram): int
    {
        $this->info('📊 Формування щоденного звіту...');

        $regionId = $this->option('region');

        if ($regionId) {
            return $this->reportForRegion($telegram, $regionId);
        }

        return $this->reportForAllRegions($telegram);
    }

    protected function reportForRegion(TelegramService $telegram, string $regionId): int
    {
        $alerts = AirAlert::forRegion($regionId)
            ->whereDate('started_at', today())
            ->get();

        if ($alerts->isEmpty()) {
            $message = '✅ <b>Звіт за '.today()->format('d.m.Y')."</b>\n\n";
            $message .= 'Сьогодні повітряних тривог не було 🎉';

            $telegram->sendMessage($message);
            $this->info('✓ Звіт надіслано');

            return Command::SUCCESS;
        }

        $totalDuration = $alerts->sum('duration_minutes');
        $regionName = $alerts->first()->region_name;

        $message = "📊 <b>Щоденний звіт</b>\n";
        $message .= '📅 '.today()->format('d.m.Y')."\n";
        $message .= "📍 Регіон: <b>{$regionName}</b>\n\n";
        $message .= "🚨 Кількість тривог: <b>{$alerts->count()}</b>\n";
        $message .= '⏱ Загальна тривалість: <b>'.round($totalDuration / 60, 1)." год</b>\n\n";

        $message .= "<b>Деталі:</b>\n";
        foreach ($alerts as $index => $alert) {
            $num = $index + 1;
            $start = $alert->started_at->format('H:i');
            $end = $alert->ended_at?->format('H:i') ?? 'триває';
            $duration = $alert->duration_minutes ? round($alert->duration_minutes / 60, 1).' год' : '-';

            $message .= "{$num}. {$start} - {$end} ({$duration})\n";
        }

        $telegram->sendMessage($message, sendToDev: true);
        $this->info('✓ Звіт надіслано');

        return Command::SUCCESS;
    }

    protected function reportForAllRegions(TelegramService $telegram): int
    {
        $alerts = AirAlert::whereDate('started_at', today())->get();

        if ($alerts->isEmpty()) {
            $message = '✅ <b>Звіт за '.today()->format('d.m.Y')."</b>\n\n";
            $message .= 'Сьогодні повітряних тривог в Україні не було 🎉';

            $telegram->sendMessage($message);
            $this->info('✓ Звіт надіслано');

            return Command::SUCCESS;
        }

        $byRegion = $alerts->groupBy('region_name');

        $message = "📊 <b>Загальний звіт по Україні</b>\n";
        $message .= '📅 '.today()->format('d.m.Y')."\n\n";
        $message .= "🚨 Тривоги зафіксовані у <b>{$byRegion->count()}</b> регіонах\n";
        $message .= "📈 Загальна кількість тривог: <b>{$alerts->count()}</b>\n\n";

        $message .= "<b>По регіонах:</b>\n";
        foreach ($byRegion as $regionName => $regionAlerts) {
            $count = $regionAlerts->count();
            $totalMinutes = $regionAlerts->sum('duration_minutes');
            $hours = $totalMinutes > 0 ? round($totalMinutes / 60, 1).' год' : '-';

            $message .= "• {$regionName}: {$count} тривог ({$hours})\n";
        }

        $telegram->sendMessage($message, sendToDev: true);
        $this->info('✓ Звіт надіслано');

        return Command::SUCCESS;
    }
}
