<?php

namespace App\Console\Commands;

use App\Jobs\SendAirAlertNotification;
use App\Models\AirAlert;
use App\Services\AirAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MonitorAirAlerts extends Command
{
    protected $signature = 'air-alert:monitor {--region= : ID регіону для моніторингу (необов\'язково)}';

    protected $description = 'Моніторинг повітряних тривог в Україні та відправка сповіщень у Telegram';

    public function handle(AirAlertService $airAlert): int
    {
        $this->info('🔍 Перевірка статусу повітряних тривог...');

        $regionId = $this->option('region');

        if ($regionId) {
            return $this->monitorRegion($airAlert, $regionId);
        }

        return $this->monitorAllRegions($airAlert);
    }

    protected function monitorRegion(AirAlertService $airAlert, string $regionId): int
    {
        $alert = $airAlert->getAlertByRegion($regionId);

        if (! $alert) {
            $this->error("❌ Не вдалося отримати дані для регіону {$regionId}");
            $this->info("💡 Можливо, використано невірний UID. Перевірте список регіонів.");

            return Command::FAILURE;
        }

        $cacheKey = "air_alert_status_{$regionId}";
        $previousStatus = Cache::get($cacheKey, false);
        $currentStatus = $alert['alert'] ?? false;

        // Якщо статус змінився
        if ($previousStatus !== $currentStatus) {
            $regionName = $alert['region_name'];

            if ($currentStatus) {
                $this->warn("🚨 ТРИВОГА! Регіон: {$regionName}");

                // Створюємо новий запис про початок тривоги
                AirAlert::create([
                    'region_id' => $regionId,
                    'region_name' => $regionName,
                    'is_active' => true,
                    'alert_type' => $alert['alert_type'],
                    'started_at' => now(),
                ]);
            } else {
                $this->info("✅ Відбій тривоги. Регіон: {$regionName}");

                // Оновлюємо останню активну тривогу - встановлюємо час закінчення
                $lastAlert = AirAlert::forRegion($regionId)
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

            // Зберігаємо новий статус
            Cache::put($cacheKey, $currentStatus, now()->addHours(24));
        } else {
            $regionName = $alert['region_name'];
            $statusText = $currentStatus ? 'активна тривога' : 'тривоги немає';
            $this->info("ℹ️ Статус не змінився для регіону {$regionName} ({$statusText})");
        }

        return Command::SUCCESS;
    }

    protected function monitorAllRegions(AirAlertService $airAlert): int
    {
        $alertsData = $airAlert->getActiveAlerts();

        if (! $alertsData) {
            $this->error('❌ Не вдалося отримати дані про тривоги');

            return Command::FAILURE;
        }

        $activeAlerts = $alertsData['alerts'] ?? [];
        $allRegions = $airAlert->getRegions();
        $changedCount = 0;

        // Створюємо мапу активних тривог за location_uid
        $activeAlertsMap = [];
        foreach ($activeAlerts as $alert) {
            $uid = $alert['location_uid'] ?? null;
            if ($uid && $alert['location_type'] === 'oblast') {
                $activeAlertsMap[$uid] = $alert;
            }
        }

        // Перевіряємо всі області
        foreach ($allRegions as $regionId => $regionName) {
            $cacheKey = "air_alert_status_{$regionId}";
            $previousStatus = Cache::get($cacheKey, null);
            $currentStatus = isset($activeAlertsMap[$regionId]);

            // Пропускаємо перший запуск (коли немає попереднього статусу)
            if ($previousStatus === null) {
                Cache::put($cacheKey, $currentStatus, now()->addHours(24));

                continue;
            }

            // Якщо статус змінився
            if ($previousStatus !== $currentStatus) {
                if ($currentStatus) {
                    $this->warn("🚨 ТРИВОГА! Регіон: {$regionName}");

                    $alertData = $activeAlertsMap[$regionId];

                    // Створюємо новий запис про початок тривоги
                    AirAlert::create([
                        'region_id' => $regionId,
                        'region_name' => $regionName,
                        'is_active' => true,
                        'alert_type' => $alertData['alert_type'] ?? 'air_raid',
                        'started_at' => now(),
                    ]);
                } else {
                    $this->info("✅ Відбій тривоги. Регіон: {$regionName}");

                    // Оновлюємо останню активну тривогу
                    $lastAlert = AirAlert::forRegion($regionId)
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

                // Зберігаємо новий статус
                Cache::put($cacheKey, $currentStatus, now()->addHours(24));
                $changedCount++;
            }
        }

        if ($changedCount === 0) {
            $this->info('ℹ️ Статус тривог не змінився');
        } else {
            $this->info("✓ Виявлено {$changedCount} змін статусу");
        }

        return Command::SUCCESS;
    }
}
