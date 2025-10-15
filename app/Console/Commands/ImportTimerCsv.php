<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;


/**
 * php artisan app:import-timer-csv
 *
 *
 * Import data from timer.csv into an array
 *
 *
 * данные модели из timer.csv
 *
 *
 *  Чекає на звіт
 *  В процесі
 *  Заплановано
 *  Рахунок виставлено
 *  Оплачено
 *  Потребує уточнення
 *
 * const STATUS_WAIT = 0;
 * const STATUS_PROCESS = 1;
 * const STATUS_PLANNED = 2;
 * const STATUS_INVOICE = 3;
 * const STATUS_PAID = 4;
 * const STATUS_NEED_CLARIFICATION = 5;
 *
 * const STATUS_ACT_OK = 'ok'; //
 * const STATUS_ACT_NOT_OK = 'not_ok'; // Не ок
 *
 * // стоимость за час
 * const PRICE = 400;
 * const COEFFICIENT_VALUE = 1.2;
 * const ARCHIVE_NO = 0; // Не архівні
 * const ARCHIVE_YES = 1; // Архівні
 *
 * static public array $statusList = [
 * self::STATUS_WAIT => 'Чекає на звіт', // 0
 * self::STATUS_PROCESS => 'В процесі', // 1
 * self::STATUS_PLANNED => 'Заплановано', // 2
 * self::STATUS_INVOICE => 'Копіювати в акти та згенерувати файл', // 3
 * self::STATUS_PAID => 'Оплачено', // 4
 * //        self::STATUS_NEED_CLARIFICATION => 'Потребує уточнення', // 5
 * ];
 *
 *
 */
class ImportTimerCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-timer-csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from timer.csv into an array';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $content = file_get_contents(base_path('storage/timer.csv'));
        $stream = fopen('data://text/plain,' . $content, 'r');
        $data = [];
        $header = fgetcsv($stream); // skip header
        while (($row = fgetcsv($stream)) !== false) {
            $data[] = $row;
        }
        fclose($stream);

        foreach ($data as $row) {
            if (count($row) < 11) {
                $this->warn("Invalid row with insufficient columns, skipping: " . json_encode($row));
                continue;
            }

            $taskGid = $row[1]; // task_gid
            $task = \App\Models\Task::where('gid', $taskGid)->first();
            if (!$task) {
                $this->warn("Task with gid {$taskGid} not found, skipping.");
                continue;
            }

            if (!$task->user_id) {
                $this->warn("Task with gid {$taskGid} has no user_id, skipping.");
                continue;
            }

            $status = match ((int) $row[6]) {
                0 => 'completed',
                1 => 'in_progress',
                2 => 'planned',
                3, 4 => 'export_akt',
                5 => 'needs_clarification',
                default => 'new',
            };

            $statusReport = match ($row[8]) {
                'ok' => 'submitted',
                'not_ok' => 'not_submitted',
                default => null,
            };

            $sec = (int) $row[3] * 60;

            \App\Models\Time::updateOrCreate(
                [
                    'task_id' => $task->id,
                    'duration' => $sec,
                ],
                [
                    'user_id' => $task->user_id,
                    'title' => $task->title,
                    'description' => $row[5], // comment
                    'coefficient' => (float) $row[4], // coefficient
                    'status' => $status,
                    'report_status' => $statusReport, // status_act
                    'is_archived' => (bool) $row[7], // archive
                    'updated_at' => $row[10], // updated_at
                    'created_at' => $row[9], // created_at
                ]
            );
        }

        $this->info('Import completed.');
    }
}
