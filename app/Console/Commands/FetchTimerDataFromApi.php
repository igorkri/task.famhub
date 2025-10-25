<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Time;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * # Базовое использование
 *  php artisan app:fetch-timer-data-from-api
 *
 * # С выводом в виде таблицы
 * php artisan app:fetch-timer-data-from-api --format=table
 *
 * # Сохранить в файл
 * php artisan app:fetch-timer-data-from-api --save
 *
 * # Импортировать данные в базу
 * php artisan app:fetch-timer-data-from-api --import
 *
 * # Импортировать с очисткой таблицы
 * php artisan app:fetch-timer-data-from-api --import --truncate
 *
 * # Кастомный URL
 * php artisan app:fetch-timer-data-from-api --url=https://api.example.com/data
 *
 * Документация:
 * docs/timer-api-command.md
 */
class FetchTimerDataFromApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-timer-data-from-api 
                            {--url= : Custom API URL to fetch data from}
                            {--save : Save data to JSON file}
                            {--import : Import data into database}
                            {--truncate : Truncate times table before import (use with --import)}
                            {--format=json : Output format (json|table)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch timer data from external API and optionally import to database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url = $this->option('url') ?? config('services.timer_api.url', 'https://asana.masterok-market.com.ua/admin/api/timer/list');

        $this->info("Fetching data from: {$url}");

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                $this->error("Failed to fetch data. Status code: {$response->status()}");

                return self::FAILURE;
            }

            $data = $response->json();

            if (empty($data)) {
                $this->warn('No data received from API');

                return self::SUCCESS;
            }

            $this->info('Data fetched successfully. Total records: '.count($data));

            // Import to database if requested
            if ($this->option('import')) {
                $this->importToDatabase($data);
            }

            // Display data based on format option
            if ($this->option('format') === 'table' && is_array($data) && count($data) > 0) {
                $this->displayAsTable($data);
            } else {
                $this->displayAsJson($data);
            }

            // Save to file if requested
            if ($this->option('save')) {
                $this->saveToFile($data);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error fetching data: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Import data to database
     */
    protected function importToDatabase(array $data): void
    {
        $this->info('Starting import to database...');

        // Truncate table if requested
        if ($this->option('truncate')) {
            $shouldTruncate = $this->option('no-interaction')
                ? true
                : $this->confirm('This will DELETE ALL records from times table. Are you sure?', false);

            if ($shouldTruncate) {
                Time::truncate();
                $this->warn('Times table truncated.');
            } else {
                $this->info('Import cancelled.');

                return;
            }
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar(count($data));
        $progressBar->start();

        foreach ($data as $record) {
            try {

// "id" => 749
//   "task_gid" => "1211692396550896"
//   "time" => "00:37:41"
//   "minute" => 37
//   "coefficient" => 1
//   "comment" => null
//   "status" => 1
//   "archive" => 0
//   "status_act" => "not_ok"
//   "created_at" => "2025-10-23 12:13:51"
//   "updated_at" => "2025-10-23 14:43:28"
//   "date_invoice" => null
//   "date_report" => null


                // Validate required fields
                if (! isset($record['task_gid'])) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                // Find task by gid
                $task = Task::where('gid', $record['task_gid'])->first();

                if (! $task) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                if (! $task->user_id) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                // Map status from API
                $status = $this->mapStatus($record['status'] ?? 0);
                $reportStatus = $this->mapReportStatus($record['status_act'] ?? null);

                // Calculate duration in seconds
                // $duration = isset($record['minutes']) ? (int) $record['minutes'] * 60 : 0;
                // time
                $duration = isset($record['time']) ? strtotime($record['time']) - strtotime('TODAY') : 0;

                // Get coefficient
                $coefficient = isset($record['coefficient']) && (float) $record['coefficient'] > 0
                    ? (float) $record['coefficient']
                    : 1.2;

                // dd([$duration, $minute]);
                // Create or update time record
                Time::updateOrCreate(
                    [
                        'task_id' => $task->id,
                        'duration' => $duration,
                        // 'created_at' => $record['created_at'] ?? now(),
                    ],
                    [
                        'user_id' => $task->user_id,
                        'title' => $task->title,
                        'description' => $record['comment'] ?? null,
                        'coefficient' => $coefficient,
                        'status' => $status,
                        'report_status' => $reportStatus ?? 'not_submitted',
                        'is_archived' => (bool) ($record['archive'] ?? false),
                        'updated_at' => $record['updated_at'] ?? now(),
                    ]
                );

                $imported++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error importing record: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Import completed:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Imported', $imported],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ]
        );
    }

    /**
     * Map API status to database status
     */
    protected function mapStatus(int $status): string
    {
        return match ($status) {
            0 => Time::STATUS_COMPLETED,
            1 => Time::STATUS_IN_PROGRESS,
            2 => Time::STATUS_PLANNED,
            3, 4 => Time::STATUS_EXPORT_AKT,
            5 => Time::STATUS_NEEDS_CLARIFICATION,
            default => Time::STATUS_NEW,
        };
    }

    /**
     * Map API report status to database report status
     */
    protected function mapReportStatus(?string $statusAct): ?string
    {
        return match ($statusAct) {
            'ok' => 'submitted',
            'not_ok' => 'not_submitted',
            default => null,
        };
    }

    /**
     * Display data as table
     */
    protected function displayAsTable(array $data): void
    {
        if (empty($data)) {
            return;
        }

        // Get headers from first item
        $headers = array_keys($data[0]);

        // Format rows
        $rows = array_map(function ($item) {
            return array_map(function ($value) {
                if (is_array($value) || is_object($value)) {
                    return json_encode($value);
                }

                return $value;
            }, array_values($item));
        }, $data);

        $this->table($headers, $rows);
    }

    /**
     * Display data as JSON
     */
    protected function displayAsJson(mixed $data): void
    {
        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Save data to JSON file
     */
    protected function saveToFile(mixed $data): void
    {
        $filename = 'timer-api-'.now()->format('Y-m-d_H-i-s').'.json';
        $path = storage_path('app/'.$filename);

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Data saved to: {$path}");
    }
}
