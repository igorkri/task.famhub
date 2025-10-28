<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\AsanaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateTaskTimestamps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asana:update-timestamps
                            {--task-id= : ID конкретної задачі для оновлення}
                            {--limit=100 : Максимальна кількість задач для обновлення}
                            {--force : Оновити всі задачі, навіть якщо timestamps вже встановлено}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Оновлює created_at і updated_at задач з даними з Asana API';

    /**
     * Execute the console command.
     */
    public function handle(AsanaService $service): int
    {
        $taskId = $this->option('task-id');
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $this->info('🕐 Запуск оновлення часових міток задач з Asana...');

        // Если указан конкретный ID задачи
        if ($taskId) {
            $task = Task::find($taskId);
            if (! $task) {
                $this->error("❌ Задачу з ID {$taskId} не знайдено");

                return self::FAILURE;
            }

            if (! $task->gid) {
                $this->error("❌ Задача {$taskId} не має Asana GID");

                return self::FAILURE;
            }

            $tasks = collect([$task]);
        } else {
            // Выбираем задачі для обновления
            $query = Task::query()->whereNotNull('gid');

            if (! $force) {
                // Оновлюємо тільки задачі, де created_at дорівнює updated_at
                // (це означає, що timestamps не були встановлені з Asana)
                $query->whereRaw('created_at = updated_at');
                $this->info('📅 Оновлюємо задачі, де timestamps не встановлено з Asana');
            } else {
                $this->warn('⚠️ Режим FORCE - оновлюємо всі задачі з Asana GID!');
            }

            $tasks = $query->limit($limit)->get();
        }

        if ($tasks->isEmpty()) {
            $this->info('✅ Немає задач для оновлення');

            return self::SUCCESS;
        }

        $this->info("📦 Знайдено задач для оновлення: {$tasks->count()}");

        $bar = $this->output->createProgressBar($tasks->count());
        $bar->start();

        $updated = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($tasks as $task) {
            try {
                // Отримуємо деталі задачі з Asana
                $taskDetails = $service->getTaskDetails($task->gid);

                if (empty($taskDetails['created_at']) && empty($taskDetails['modified_at'])) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                // Підготовка даних для оновлення
                $updateData = [];

                if (! empty($taskDetails['created_at'])) {
                    // Конвертуємо ISO 8601 формат (2022-07-27T11:38:56.498Z) в MySQL формат
                    $createdAt = \Carbon\Carbon::parse($taskDetails['created_at']);
                    $updateData['created_at'] = $createdAt->format('Y-m-d H:i:s');
                }

                if (! empty($taskDetails['modified_at'])) {
                    // Конвертуємо ISO 8601 формат (2022-08-14T09:39:24.629Z) в MySQL формат
                    $modifiedAt = \Carbon\Carbon::parse($taskDetails['modified_at']);
                    $updateData['updated_at'] = $modifiedAt->format('Y-m-d H:i:s');
                }

                if (! empty($updateData)) {
                    // Використовуємо DB::table для обходу автоматичного оновлення timestamps
                    DB::table('tasks')
                        ->where('id', $task->id)
                        ->update($updateData);

                    $updated++;

                    Log::info('Оновлено timestamps задачі', [
                        'task_id' => $task->id,
                        'task_gid' => $task->gid,
                        'created_at' => $updateData['created_at'] ?? null,
                        'updated_at' => $updateData['updated_at'] ?? null,
                    ]);
                }

                $bar->advance();
            } catch (\Exception $e) {
                $errors++;
                Log::error('Помилка оновлення timestamps задачі', [
                    'task_id' => $task->id,
                    'task_gid' => $task->gid ?? null,
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
}
