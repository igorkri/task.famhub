<?php

namespace App\Console\Commands;

use App\Jobs\SendAirAlertNotification;
use App\Models\AirAlert;
use App\Services\AirAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MonitorPoltavaRegion extends Command
{
    protected $signature = 'air-alert:monitor-poltava {--all : Показати всі активні тривоги в області} {--city : Тільки м. Полтава (область)}';

    protected $description = 'Моніторинг повітряних тривог у Полтавській області';

    /**
     * Список всіх громад Полтавського району
     */
    protected array $poltavaHromadas = [
        '109' => 'Полтавський район',
        '1042' => 'Білицька територіальна громада',
        '1043' => 'Великорублівська територіальна громада',
        '1044' => 'Диканьська територіальна громада',
        '1045' => 'Драбинівська територіальна громада',
        '1046' => 'Зіньківська територіальна громада',
        '1047' => 'Карлівська територіальна громада',
        '1048' => 'Кобеляцька територіальна громада',
        '1049' => 'Коломацька територіальна громада',
        '1050' => 'Котелевська територіальна громада',
        '1051' => 'Ланнівська територіальна громада',
        '1052' => 'Мартинівська територіальна громада',
        '1053' => 'Мачухівська територіальна громада',
        '1054' => 'Машівська територіальна громада',
        '1055' => 'Михайлівська територіальна громада',
        '1056' => 'Нехворощанська територіальна громада',
        '1057' => 'Новосанжарська територіальна громада',
        '1058' => 'Новоселівська територіальна громада',
        '1059' => 'Опішнянська територіальна громада',
        '1060' => 'м. Полтава та Полтавська територіальна громада',
        '1061' => 'Решетилівська територіальна громада',
        '1062' => 'Скороходівська територіальна громада',
        '1063' => 'Терешківська територіальна громада',
        '1064' => 'Чутівська територіальна громада',
        '1065' => 'Щербанівська територіальна громада',
    ];

    public function handle(AirAlertService $airAlert): int
    {
        $this->info('🔍 Моніторинг Полтавського регіону...');

        if ($this->option('city')) {
            return $this->monitorCity($airAlert);
        }

        if ($this->option('all')) {
            return $this->monitorAllHromadas($airAlert);
        }

        // За замовчуванням моніторимо тільки місто Полтава та район
        return $this->monitorCityAndRaion($airAlert);
    }

    protected function monitorCity(AirAlertService $airAlert): int
    {
        $this->info('📍 Моніторинг: м. Полтава');

        return $this->checkRegion($airAlert, '1060');
    }

    protected function monitorCityAndRaion(AirAlertService $airAlert): int
    {
        $this->info('📍 Моніторинг: м. Полтава та Полтавський район');

        $cityResult = $this->checkRegion($airAlert, '1060');
        $raionResult = $this->checkRegion($airAlert, '109');

        return $cityResult === Command::SUCCESS && $raionResult === Command::SUCCESS
            ? Command::SUCCESS
            : Command::FAILURE;
    }

    protected function monitorAllHromadas(AirAlertService $airAlert): int
    {
        $this->info('📍 Моніторинг: громади Полтавської області');

        // Отримуємо всі активні тривоги для Полтавської області
        $poltavaAlerts = $airAlert->getActiveAlertsForOblast('Полтавська область');

        if ($poltavaAlerts === null) {
            $this->error('❌ Не вдалося отримати дані про тривоги');

            return Command::FAILURE;
        }

        if (empty($poltavaAlerts)) {
            $this->info('✅ Тривог у Полтавській області немає');

            return Command::SUCCESS;
        }

        $this->info('🚨 Знайдено активних тривог: '.count($poltavaAlerts));
        $this->newLine();

        foreach ($poltavaAlerts as $alert) {
            $location = $alert['location_title'] ?? 'Невідома локація';
            $type = $alert['location_type'] ?? 'unknown';
            $alertType = $alert['alert_type'] ?? 'air_raid';
            $startedAt = $alert['started_at'] ?? null;

            $typeEmoji = match ($type) {
                'oblast' => '🏛️',
                'raion' => '📍',
                'hromada' => '🏘️',
                'city' => '🏙️',
                default => '📌',
            };

            $alertTypeText = match ($alertType) {
                'air_raid' => 'Повітряна тривога',
                'artillery_shelling' => 'Артилерійський обстріл',
                'urban_fights' => 'Міські бої',
                'chemical' => 'Хімічна загроза',
                'nuclear' => 'Ядерна загроза',
                default => 'Тривога',
            };

            $this->warn("{$typeEmoji} {$location}");
            $this->line("   Тип: {$alertTypeText}");
            if ($startedAt) {
                $started = \Carbon\Carbon::parse($startedAt);
                $duration = $started->diffForHumans();
                $this->line("   Почалась: {$duration}");
            }
            $this->newLine();
        }

        return Command::SUCCESS;
    }

    protected function checkRegion(AirAlertService $airAlert, string $regionUid, bool $verbose = true): int
    {
        $alert = $airAlert->getAlertByRegion($regionUid);

        if (! $alert) {
            if ($verbose) {
                $regionName = $this->poltavaHromadas[$regionUid] ?? $regionUid;
                $this->error("❌ Не вдалося отримати дані для {$regionName}");
            }

            return Command::FAILURE;
        }

        $cacheKey = "air_alert_status_{$regionUid}";
        $previousStatus = Cache::get($cacheKey, null);
        $currentStatus = $alert['alert'] ?? false;

        // Пропускаємо перший запуск
        if ($previousStatus === null) {
            Cache::put($cacheKey, $currentStatus, now()->addHours(24));

            return Command::INVALID;
        }

        // Якщо статус змінився
        if ($previousStatus !== $currentStatus) {
            $regionName = $alert['region_name'];

            if ($currentStatus) {
                $this->warn("🚨 ТРИВОГА! {$regionName}");

                AirAlert::create([
                    'region_id' => $regionUid,
                    'region_name' => $regionName,
                    'is_active' => true,
                    'alert_type' => $alert['alert_type'],
                    'started_at' => now(),
                ]);
            } else {
                $this->info("✅ Відбій тривоги. {$regionName}");

                $lastAlert = AirAlert::forRegion($regionUid)
                    ->active()
                    ->latest()
                    ->first();

                if ($lastAlert) {
                    $lastAlert->update([
                        'is_active' => false,
                        'ended_at' => now(),
                    ]);
                    $lastAlert->calculateDuration();
                }
            }

            // Відправляємо повідомлення в Telegram
            SendAirAlertNotification::dispatch(
                region: $regionName,
                isActive: $currentStatus,
                additionalInfo: null
            );

            Cache::put($cacheKey, $currentStatus, now()->addHours(24));

            return Command::SUCCESS;
        }

        if ($verbose) {
            $statusText = $currentStatus ? 'активна тривога' : 'тривоги немає';
            $this->info("ℹ️ {$alert['region_name']}: {$statusText}");
        }

        return Command::INVALID;
    }
}
