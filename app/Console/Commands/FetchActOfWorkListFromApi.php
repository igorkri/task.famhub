<?php

namespace App\Console\Commands;

use App\Models\ActOfWork;
use App\Models\ActOfWorkDetail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchActOfWorkListFromApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-act-of-work-list-from-api 
                            {--url= : Custom API URL to fetch data from}
                            {--save : Save data to JSON file}
                            {--import : Import data into database}
                            {--with-details : Also import details for each act (use with --import)}
                            {--truncate : Truncate act_of_works table before import (use with --import)}
                            {--format=json : Output format (json|table)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch act of work list from external API and optionally import to database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url = $this->option('url') ?? config('services.act_of_work_api.list_url', 'https://asana.masterok-market.com.ua/admin/api/act-of-work/list');

        $this->info("Fetching act of work list from: {$url}");

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
                : $this->confirm('This will DELETE ALL records from act_of_works table. Are you sure?', false);

            if ($shouldTruncate) {
                ActOfWork::truncate();
                $this->warn('ActOfWorks table truncated.');
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
                // Validate required fields
                if (! isset($record['number'])) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                // Find user
                $user = null;
                if (isset($record['user_id'])) {
                    $user = User::find($record['user_id']);
                }

                if (! $user) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                // Map status
                $status = $this->mapStatus($record['status'] ?? 'pending');

                // Parse period if it's an array or JSON
                $period = $record['period'] ?? null;
                if (is_string($period)) {
                    $period = json_decode($period, true) ?? $period;
                }

                // Create or update act of work record
                $actOfWork = ActOfWork::updateOrCreate(
                    [
                        'number' => $record['number'],
                        'user_id' => $user->id,
                    ],
                    [
                        'status' => $status,
                        'period' => $period,
                        'period_type' => $record['period_type'] ?? null,
                        'period_year' => $record['period_year'] ?? null,
                        'period_month' => $record['period_month'] ?? null,
                        'date' => $record['date'] ?? now(),
                        'description' => $record['description'] ?? null,
                        'total_amount' => $record['total_amount'] ?? 0,
                        'paid_amount' => $record['paid_amount'] ?? 0,
                        'file_excel' => $record['file_excel'] ?? null,
                        'sort' => $record['sort'] ?? 0,
                        'telegram_status' => $record['telegram_status'] ?? 'pending',
                        'type' => $record['type'] ?? ActOfWork::TYPE_ACT,
                        'created_at' => $record['created_at'] ?? now(),
                        'updated_at' => $record['updated_at'] ?? now(),
                    ]
                );

                $imported++;

                // Import details if flag is set
                if ($this->option('with-details')) {
                    $this->importActDetails($actOfWork, $record['id']);
                }
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
    protected function mapStatus(string $status): string
    {
        $statusMap = [
            'pending' => ActOfWork::STATUS_PENDING,
            'in_progress' => ActOfWork::STATUS_IN_PROGRESS,
            'paid' => ActOfWork::STATUS_PAID,
            'partially_paid' => ActOfWork::STATUS_PARTIALLY_PAID,
            'cancelled' => ActOfWork::STATUS_CANCELLED,
            'archived' => ActOfWork::STATUS_ARCHIVED,
            'draft' => ActOfWork::STATUS_DRAFT,
            'done' => ActOfWork::STATUS_DONE,
        ];

        return $statusMap[$status] ?? ActOfWork::STATUS_PENDING;
    }

    /**
     * Import details for a specific act of work
     */
    protected function importActDetails(ActOfWork $actOfWork, string $actNumber): void
    {
        try {
        
            $baseUrl = config('services.act_of_work_api.detail_url', 'https://asana.masterok-market.com.ua/admin/api/act-of-work-detail/by-act');
            $url = $baseUrl.'?act_id='.$actNumber;

            
            $response = Http::timeout(30)->get($url);
            
            if (! $response->successful()) {
                $this->newLine();
                $this->warn("Failed to fetch details for act {$actNumber}. Status: {$response->status()}");

                return;
            }

            $details = $response->json();

            if (empty($details) || ! is_array($details)) {
                $this->warn("No valid details found for act {$actNumber}");
                return;
            }

            // dump($details['data']); die;

            foreach ($details['data'] as $detail) {
                
                // Validate required fields
                if (! isset($detail['task_gid']) && ! isset($detail['project_gid'])) {
                    dump($detail);
                    $this->warn("No valid task or project ID found for act {$actNumber}");
                    continue;
                }

                // Create or update detail record
                ActOfWorkDetail::updateOrCreate(
                    [
                        'act_of_work_id' => $actOfWork->id,
                        'task_gid' => $detail['task_gid'] ?? null,
                        'project_gid' => $detail['project_gid'] ?? null,
                    ],
                    [
                        'time_id' => $detail['time_id'] ?? null,
                        'project' => $detail['project'] ?? null,
                        'task' => $detail['task'] ?? null,
                        'description' => $detail['description'] ?? null,
                        'amount' => $detail['amount'] ?? 0,
                        'hours' => $detail['hours'] ?? 0,
                        'created_at' => $detail['created_at'] ?? now(),
                        'updated_at' => $detail['updated_at'] ?? now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->newLine();
            $this->warn("Error importing details for act {$actNumber}: {$e->getMessage()}");
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
        $filename = 'act-of-work-list-'.now()->format('Y-m-d_H-i-s').'.json';
        $path = storage_path('app/'.$filename);

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Data saved to: {$path}");
    }
}
