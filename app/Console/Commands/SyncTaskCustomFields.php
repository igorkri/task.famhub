<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskCustomField;
use App\Services\AsanaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTaskCustomFields extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'asana:sync-custom-fields
                            {--task= : Sync only specific task by ID}
                            {--project= : Sync only tasks from specific project ID}
                            {--force : Overwrite existing custom fields}';

    /**
     * The console command description.
     */
    protected $description = 'Sync custom fields from Asana for all tasks';

    /**
     * Execute the console command.
     */
    public function handle(AsanaService $service): int
    {
        $taskId = $this->option('task');
        $projectId = $this->option('project');
        $force = $this->option('force');

        // Отримуємо таски для синхронізації
        $query = Task::query()->whereNotNull('gid');

        if ($taskId) {
            $query->where('id', $taskId);
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            $this->warn('Таски для синхронізації не знайдено');

            return self::SUCCESS;
        }

        $this->info("Знайдено тасків для синхронізації: {$tasks->count()}");
        $this->newLine();

        $bar = $this->output->createProgressBar($tasks->count());
        $bar->start();

        $synced = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($tasks as $task) {
            try {
                // Отримуємо деталі таску з Asana
                $taskDetails = $service->getTaskDetails($task->gid);

                if (empty($taskDetails['custom_fields'])) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                // Синхронізуємо кастомні поля
                foreach ($taskDetails['custom_fields'] as $customField) {
                    $existingField = TaskCustomField::where('task_id', $task->id)
                        ->where('asana_gid', $customField['gid'])
                        ->first();

                    // Пропускаємо, якщо поле вже існує і не встановлено --force
                    if ($existingField && ! $force) {
                        continue;
                    }

                    // Знаходимо відповідне ProjectCustomField
                    $projectCustomField = \App\Models\ProjectCustomField::where('project_id', $task->project_id)
                        ->where('asana_gid', $customField['gid'])
                        ->first();

                    TaskCustomField::updateOrCreate(
                        [
                            'task_id' => $task->id,
                            'asana_gid' => $customField['gid'],
                        ],
                        [
                            'project_custom_field_id' => $projectCustomField?->id,
                            'name' => $customField['name'],
                            'type' => $customField['type'],
                            'text_value' => $customField['text_value'] ?? null,
                            'number_value' => $customField['number_value'] ?? null,
                            'enum_value_gid' => $customField['enum_value']['gid'] ?? null,
                            'enum_value_name' => $customField['enum_value']['name'] ?? null,
                        ]
                    );
                }

                $synced++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to sync custom fields for task', [
                    'task_id' => $task->id,
                    'task_gid' => $task->gid,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Показуємо результати
        $this->info("✓ Синхронізовано: {$synced}");

        if ($skipped > 0) {
            $this->line("○ Пропущено (без custom fields): {$skipped}");
        }

        if ($errors > 0) {
            $this->warn("✗ Помилок: {$errors}");
        }

        $this->newLine();
        $this->info('✓ Синхронізація завершена!');

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
