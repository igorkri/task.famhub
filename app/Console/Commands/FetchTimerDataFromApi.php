<?php

namespace App\Console\Commands;

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
 * # Кастомный URL
 * php artisan app:fetch-timer-data-from-api --url=https://api.example.com/data
 * 
 * Документация:
 * docs/timer-api-command.md 
 * 
 * 
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
                            {--format=json : Output format (json|table)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch timer data from external API';

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
