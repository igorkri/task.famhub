<?php

namespace App\Console\Commands;

use App\Models\Time;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateTimeTimestamps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'masterok:update-time-timestamps
                            {--time-id= : ID конкретного запису часу для оновлення}
                            {--url= : Custom API URL}
                            {--limit=100 : Максимальна кількість записів для оновлення}
                            {--force : Оновити всі записи, навіть якщо timestamps вже встановлено}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Оновлює created_at і updated_at записів часу з даними з Masterok Market API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timeId = $this->option('time-id');
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');
        $url = $this->option('url') ?? config('services.timer_api.url', 'https://asana.masterok-market.com.ua/admin/api/timer/list');

        $this->info('🕐 Запуск оновлення часових міток записів часу з Masterok Market API...');

        // Получаем данные из API
        $this->info("📡 Отримання даних з API: {$url}");

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                $this->error("❌ Помилка отримання даних. Статус код: {$response->status()}");

                return self::FAILURE;
            }

            $apiData = $response->json();

            if (empty($apiData)) {
                $this->warn('⚠️ API повернув порожні дані');

                return self::SUCCESS;
            }

            $this->info('✅ Отримано записів з API: '.count($apiData));

        } catch (\Exception $e) {
            $this->error('❌ Помилка при зверненні до API: '.$e->getMessage());

            return self::FAILURE;
        }

        // Индексируем API данные по task_gid для быстрого поиска
        $apiDataByTaskGid = [];
        foreach ($apiData as $record) {
            if (isset($record['task_gid'])) {
                $key = $record['task_gid'];
                if (! isset($apiDataByTaskGid[$key])) {
                    $apiDataByTaskGid[$key] = [];
                }
                $apiDataByTaskGid[$key][] = $record;
            }
        }

        // Если указан конкретный ID записи времени
        if ($timeId) {
            $time = Time::find($timeId);
            if (! $time) {
                $this->error("❌ Запис часу з ID {$timeId} не знайдено");

                return self::FAILURE;
            }

            $times = collect([$time]);
        } else {
            // Выбираем записи для обновления
            $query = Time::query()
                ->with('task:id,gid');

            if (! $force) {
                // Оновлюємо тільки записи, де created_at дорівнює updated_at
                $query->whereRaw('created_at = updated_at');
                $this->info('📅 Оновлюємо записи, де timestamps не встановлено з API');
            } else {
                $this->warn('⚠️ Режим FORCE - оновлюємо всі записи часу!');
            }

            $times = $query->limit($limit)->get();
        }

        if ($times->isEmpty()) {
            $this->info('✅ Немає записів для оновлення');

            return self::SUCCESS;
        }

        $this->info("📦 Знайдено записів для оновлення: {$times->count()}");

        $bar = $this->output->createProgressBar($times->count());
        $bar->start();

        $updated = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($times as $time) {
            try {
                // Получаем task_gid
                $taskGid = $time->task?->gid;

                if (! $taskGid) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                // Ищем данные в API по task_gid и duration
                $apiRecord = $this->findMatchingApiRecord($apiDataByTaskGid[$taskGid] ?? [], $time);

                if (! $apiRecord) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                if (empty($apiRecord['created_at']) && empty($apiRecord['updated_at'])) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                // Підготовка даних для оновлення
                $updateData = [];

                if (! empty($apiRecord['created_at'])) {
                    $updateData['created_at'] = $apiRecord['created_at'];
                }

                if (! empty($apiRecord['updated_at'])) {
                    $updateData['updated_at'] = $apiRecord['updated_at'];
                }

                if (! empty($updateData)) {
                    // Використовуємо DB::table для обходу автоматичного оновлення timestamps
                    DB::table('times')
                        ->where('id', $time->id)
                        ->update($updateData);

                    $updated++;

                    Log::info('Оновлено timestamps запису часу', [
                        'time_id' => $time->id,
                        'task_id' => $time->task_id,
                        'task_gid' => $taskGid,
                        'created_at' => $updateData['created_at'] ?? null,
                        'updated_at' => $updateData['updated_at'] ?? null,
                    ]);
                }

                $bar->advance();
            } catch (\Exception $e) {
                $errors++;
                Log::error('Помилка оновлення timestamps запису часу', [
                    'time_id' => $time->id,
                    'task_id' => $time->task_id ?? null,
                    'error' => $e->getMessage(),
                ]);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Статистика
        $this->info("✅ Оновлено: {$updated}");
        if ($skipped > 0) {
            $this->warn("⚠️ Пропущено (немає даних): {$skipped}");
        }
        if ($errors > 0) {
            $this->error("❌ Помилок: {$errors}");
        }

        $this->newLine();
        $this->info('🎉 Оновлення завершено!');

        return self::SUCCESS;
    }

    /**
     * Найти соответствующую запись API по task_gid и duration
     */
    protected function findMatchingApiRecord(array $apiRecords, Time $time): ?array
    {
        if (empty($apiRecords)) {
            return null;
        }

        // Пытаемся найти точное совпадение по duration
        foreach ($apiRecords as $record) {
            $apiDuration = isset($record['time']) ? strtotime($record['time']) - strtotime('TODAY') : 0;

            if ($apiDuration === $time->duration) {
                return $record;
            }
        }

        // Если точного совпадения нет, возвращаем первую запись
        return $apiRecords[0] ?? null;
    }
}
