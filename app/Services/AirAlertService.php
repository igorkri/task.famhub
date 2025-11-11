<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirAlertService
{
    protected string $apiToken;

    protected string $baseUrl = 'https://api.alerts.in.ua/v1';

    /**
     * Мапа регіонів України (UID з alerts.in.ua API)
     */
    protected array $regionMap = [
        '3' => 'Хмельницька область',
        '4' => 'Вінницька область',
        '5' => 'Рівненська область',
        '8' => 'Волинська область',
        '9' => 'Дніпропетровська область',
        '10' => 'Житомирська область',
        '11' => 'Закарпатська область',
        '12' => 'Запорізька область',
        '13' => 'Івано-Франківська область',
        '14' => 'Київська область',
        '15' => 'Кіровоградська область',
        '16' => 'Луганська область',
        '17' => 'Миколаївська область',
        '18' => 'Одеська область',
        '19' => 'Полтавська область',
        '20' => 'Сумська область',
        '21' => 'Тернопільська область',
        '22' => 'Харківська область',
        '23' => 'Херсонська область',
        '24' => 'Черкаська область',
        '25' => 'Чернігівська область',
        '26' => 'Чернівецька область',
        '27' => 'Львівська область',
        '28' => 'Донецька область',
        '29' => 'Автономна Республіка Крим',
        '30' => 'м. Севастополь',
        '31' => 'м. Київ',
        // Полтавський район та громади
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

    public function __construct()
    {
        $this->apiToken = config('services.air_alert.token');
    }

    /**
     * Отримати поточний статус тривог по всіх регіонах
     */
    public function getActiveAlerts(): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('Air Alert API token not configured');

            return null;
        }

        try {
            $response = Http::get("{$this->baseUrl}/alerts/active.json", [
                'token' => $this->apiToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get active alerts', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting active alerts', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Отримати статус тривоги для конкретного регіону (IoT endpoint)
     * Працює тільки для областей (UID 3-31)
     */
    public function getAlertByRegion(string $regionUid): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('Air Alert API token not configured');

            return null;
        }

        try {
            $response = Http::get("{$this->baseUrl}/iot/active_air_raid_alerts/{$regionUid}.json", [
                'token' => $this->apiToken,
            ]);

            if ($response->successful()) {
                $status = trim($response->body(), '"');
                $regionName = $this->regionMap[$regionUid] ?? "Регіон {$regionUid}";

                return [
                    'region_id' => $regionUid,
                    'region_name' => $regionName,
                    'alert' => $status === 'A',
                    'alert_type' => $status === 'A' ? 'air_raid' : null,
                    'status_code' => $status, // A - активна, P - часткова, N - немає
                ];
            }

            Log::error('Failed to get alert for region', [
                'region_uid' => $regionUid,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting alert for region', [
                'region_uid' => $regionUid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Отримати активні тривоги для конкретної області з деталями громад
     */
    public function getActiveAlertsForOblast(string $oblastName): ?array
    {
        $alerts = $this->getActiveAlerts();

        if (! $alerts || ! isset($alerts['alerts'])) {
            return null;
        }

        $filtered = array_filter($alerts['alerts'], function ($alert) use ($oblastName) {
            return isset($alert['location_oblast']) &&
                   mb_strtolower($alert['location_oblast']) === mb_strtolower($oblastName);
        });

        return array_values($filtered);
    }

    /**
     * Отримати список всіх регіонів
     */
    public function getRegions(): array
    {
        return $this->regionMap;
    }

    /**
     * Отримати історію тривог для регіону
     */
    public function getAlertHistory(string $regionUid, string $period = 'month_ago'): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('Air Alert API token not configured');

            return null;
        }

        try {
            $response = Http::get("{$this->baseUrl}/regions/{$regionUid}/alerts/{$period}.json", [
                'token' => $this->apiToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get alert history', [
                'region_uid' => $regionUid,
                'period' => $period,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting alert history', [
                'region_uid' => $regionUid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Отримати статуси всіх областей (компактний формат)
     */
    public function getAlertStatusesByOblast(): ?string
    {
        if (! $this->isConfigured()) {
            Log::warning('Air Alert API token not configured');

            return null;
        }

        try {
            $response = Http::get("{$this->baseUrl}/iot/active_air_raid_alerts_by_oblast.json", [
                'token' => $this->apiToken,
            ]);

            if ($response->successful()) {
                return trim($response->body(), '"');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting oblast statuses', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Перевірити чи є активна тривога в регіоні
     */
    public function isAlertActive(string $regionUid): bool
    {
        $alert = $this->getAlertByRegion($regionUid);

        if (! $alert) {
            return false;
        }

        return $alert['alert'] ?? false;
    }

    /**
     * Знайти UID регіону по назві
     */
    public function findRegionUid(string $regionName): ?string
    {
        $regionName = mb_strtolower($regionName);

        foreach ($this->regionMap as $uid => $name) {
            if (mb_strtolower($name) === $regionName) {
                return $uid;
            }
        }

        return null;
    }

    /**
     * Перевірка конфігурації
     */
    protected function isConfigured(): bool
    {
        return ! empty($this->apiToken);
    }
}
