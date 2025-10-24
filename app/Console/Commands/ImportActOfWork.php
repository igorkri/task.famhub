<?php

namespace App\Console\Commands;

use App\Models\ActOfWork;
use App\Models\ActOfWorkDetail;
use Illuminate\Console\Command;

class ImportActOfWork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-act-of-work {--file=storage/old-asana.sql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Імпорт даних act_of_work та act_of_work_detail з SQL дампа';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = base_path($this->option('file'));

        if (! file_exists($filePath)) {
            $this->error("Файл не знайдено: {$filePath}");

            return self::FAILURE;
        }

        $this->info('Початок імпорту даних з act_of_work та act_of_work_detail...');

        try {
            // Читаємо SQL файл
            $sql = file_get_contents($filePath);

            // Витягуємо INSERT для act_of_work
            if (preg_match('/INSERT INTO `act_of_work`[^;]+;/s', $sql, $matches)) {
                $this->info('Імпорт act_of_work...');

                // Парсимо дані з INSERT
                $insertSql = $matches[0];
                if (preg_match('/VALUES\s+(.+);$/s', $insertSql, $valuesMatch)) {
                    $valuesString = $valuesMatch[1];

                    // Розбиваємо на окремі записи
                    preg_match_all('/\(([^)]+(?:\([^)]*\)[^)]*)*)\)/s', $valuesString, $recordMatches);

                    $imported = 0;
                    foreach ($recordMatches[1] as $recordString) {
                        $values = $this->parseValues($recordString);

                        if (count($values) >= 17) {
                            ActOfWork::create([
                                'number' => $values[1],
                                'status' => $values[2],
                                'period' => $this->parseJson($values[3]),
                                'period_type' => $values[4],
                                'period_year' => $values[5],
                                'period_month' => $values[6],
                                'user_id' => $values[7],
                                'date' => $values[8],
                                'description' => $values[9],
                                'total_amount' => $values[10],
                                'paid_amount' => $values[11],
                                'file_excel' => $values[12],
                                'created_at' => $values[13],
                                'updated_at' => $values[14],
                                'sort' => $values[15],
                                'telegram_status' => $values[16],
                                'type' => $values[17] ?? '',
                            ]);
                            $imported++;
                        }
                    }

                    $this->info("Імпортовано {$imported} записів act_of_work");
                }
            }

            // Витягуємо INSERT для act_of_work_detail
            if (preg_match('/INSERT INTO `act_of_work_detail`[^;]+;/s', $sql, $matches)) {
                $this->info('Імпорт act_of_work_detail...');

                $insertSql = $matches[0];
                if (preg_match('/VALUES\s+(.+);$/s', $insertSql, $valuesMatch)) {
                    $valuesString = $valuesMatch[1];

                    preg_match_all('/\(([^)]+(?:\([^)]*\)[^)]*)*)\)/s', $valuesString, $recordMatches);

                    $imported = 0;
                    foreach ($recordMatches[1] as $recordString) {
                        $values = $this->parseValues($recordString);

                        if (count($values) >= 11) {
                            ActOfWorkDetail::create([
                                'act_of_work_id' => $values[1],
                                'time_id' => $values[2],
                                'task_gid' => $values[3],
                                'project_gid' => $values[4],
                                'project' => $values[5],
                                'task' => $values[6],
                                'description' => $values[7],
                                'amount' => $values[8],
                                'hours' => $values[9],
                                'created_at' => $values[10],
                                'updated_at' => $values[11],
                            ]);
                            $imported++;
                        }
                    }

                    $this->info("Імпортовано {$imported} записів act_of_work_detail");
                }
            }

            $this->info('Імпорт завершено успішно!');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Помилка при імпорті: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    private function parseValues(string $recordString): array
    {
        $values = [];
        $current = '';
        $inString = false;
        $escapeNext = false;

        for ($i = 0; $i < strlen($recordString); $i++) {
            $char = $recordString[$i];

            if ($escapeNext) {
                $current .= $char;
                $escapeNext = false;

                continue;
            }

            if ($char === '\\') {
                $escapeNext = true;

                continue;
            }

            if ($char === "'" && ! $escapeNext) {
                $inString = ! $inString;

                continue;
            }

            if ($char === ',' && ! $inString) {
                $values[] = $this->cleanValue(trim($current));
                $current = '';

                continue;
            }

            $current .= $char;
        }

        if ($current !== '') {
            $values[] = $this->cleanValue(trim($current));
        }

        return $values;
    }

    private function cleanValue(string $value): mixed
    {
        if ($value === 'NULL') {
            return null;
        }

        return $value;
    }

    private function parseJson(string $value): ?array
    {
        if ($value === 'NULL' || empty($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
