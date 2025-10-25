<?php

namespace App\Console\Commands;

use App\Models\ActOfWork;
use App\Models\ActOfWorkDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * # Базовое использование
 * php artisan app:fetch-act-of-work-detail-from-api --act-id=23
 *
 * # С выводом в виде таблицы
 * php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --format=table
 *
 * # Сохранить в файл
 * php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --save
 *
 * # Кастомный URL
 * php artisan app:fetch-act-of-work-detail-from-api --act-id=23 --url=https://api.example.com/data
 */
class FetchActOfWorkDetailFromApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-act-of-work-detail-from-api 
                            {--act-id= : Act of work ID to fetch details for}
                            {--url= : Custom API URL to fetch data from}
                            {--save : Save data to JSON file}
                            {--import : Import data into database}
                            {--truncate : Truncate act_of_work_details table before import (use with --import)}
                            {--format=json : Output format (json|table)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch act of work detail from external API by act ID and optionally import to database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $actId = $this->option('act-id');

        if (! $actId) {
            $this->error('Act ID is required. Use --act-id option.');

            return self::FAILURE;
        }

        $baseUrl = config('services.act_of_work_api.detail_url', 'https://asana.masterok-market.com.ua/admin/api/act-of-work-detail/by-act');
        $url = $this->option('url') ?? $baseUrl.'?act_id='.$actId;

        $this->info("Fetching act of work detail from: {$url}");

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

            if (is_array($data) && isset($data[0])) {
                $this->info('Data fetched successfully. Total records: '.count($data));
            } else {
                $this->info('Data fetched successfully.');
            }

            // Import to database if requested
            if ($this->option('import')) {
                $this->importToDatabase($data, $actId);
            }

            // Display data based on format option
            if ($this->option('format') === 'table' && is_array($data) && count($data) > 0) {
                $this->displayAsTable($data);
            } else {
                $this->displayAsJson($data);
            }

            // Save to file if requested
            if ($this->option('save')) {
                $this->saveToFile($data, $actId);
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
    protected function importToDatabase(array $data, string $actId): void
    {
        $this->info('Starting import to database...');

        // Truncate table if requested
        if ($this->option('truncate')) {
            $shouldTruncate = $this->option('no-interaction')
                ? true
                : $this->confirm('This will DELETE ALL records from act_of_work_details table. Are you sure?', false);

            if ($shouldTruncate) {
                ActOfWorkDetail::truncate();
                $this->warn('ActOfWorkDetails table truncated.');
            } else {
                $this->info('Import cancelled.');

                return;
            }
        }

        // Find parent act of work
        $actOfWork = ActOfWork::where('number', $actId)
            ->orWhere('id', $actId)
            ->first();

        if (! $actOfWork) {
            $this->error("Act of work not found with ID/number: {$actId}");
            $this->warn('Please import act of works first using: php artisan app:fetch-act-of-work-list-from-api --import');

            return;
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar(count($data));
        $progressBar->start();

        foreach ($data as $record) {
            try {
                // Validate required fields
                if (! isset($record['task_gid']) && ! isset($record['project_gid'])) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                // Create or update act of work detail record
                ActOfWorkDetail::updateOrCreate(
                    [
                        'act_of_work_id' => $actOfWork->id,
                        'task_gid' => $record['task_gid'] ?? null,
                        'project_gid' => $record['project_gid'] ?? null,
                    ],
                    [
                        'time_id' => $record['time_id'] ?? null,
                        'project' => $record['project'] ?? null,
                        'task' => $record['task'] ?? null,
                        'description' => $record['description'] ?? null,
                        'amount' => $record['amount'] ?? 0,
                        'hours' => $record['hours'] ?? 0,
                        'created_at' => $record['created_at'] ?? now(),
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
    protected function saveToFile(mixed $data, string $actId): void
    {
        $filename = 'act-of-work-detail-'.$actId.'-'.now()->format('Y-m-d_H-i-s').'.json';
        $path = storage_path('app/'.$filename);

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Data saved to: {$path}");
    }
}
