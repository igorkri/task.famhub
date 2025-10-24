<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\AsanaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAsanaTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asana:sync-tasks
                            {--hours=24 : Синхронізувати задачі, які не оновлювалися N годин}
                            {--limit=50 : Максимальна кількість задач для синхронізації}
                            {--force : Синхронізувати всі задачі незалежно від часу}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Резервна синхронізація задач з Asana (для випадків, коли webhook не спрацював)';

    /**
     * Execute the console command.
     */
    public function handle(AsanaService $service): int
    {
        $hours = (int) $this->option('hours');
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $this->info('🔄 Запуск резервної синхронізації задач з Asana...');

        // Вибираємо задачі для синхронізації
        $query = Task::query()
            ->whereNotNull('gid')
            ->where('is_completed', false);

        if (! $force) {
            $query->where('updated_at', '<', now()->subHours($hours));
            $this->info("📅 Синхронізуємо задачі, які не оновлювалися {$hours} годин");
        } else {
            $this->warn('⚠️ Режим FORCE - синхронізуємо всі незавершені задачі!');
        }

        $tasks = $query->limit($limit)->get();

        if ($tasks->isEmpty()) {
            $this->info('✅ Немає задач для синхронізації');

            return self::SUCCESS;
        }

        $this->info("📦 Знайдено задач для синхронізації: {$tasks->count()}");

        $bar = $this->output->createProgressBar($tasks->count());
        $bar->start();

        $synced = 0;
        $errors = 0;

        foreach ($tasks as $task) {
            try {
                // Отримуємо деталі задачі з Asana
                $taskDetails = $service->getTaskDetails($task->gid);

                // Оновлюємо основні поля
                $updateData = [
                    'title' => $taskDetails['name'] ?? $task->title,
                    'description' => $taskDetails['notes'] ?? $task->description,
                    'is_completed' => $taskDetails['completed'] ?? $task->is_completed,
                    'deadline' => $taskDetails['due_on'] ?? $task->deadline,
                    'start_date' => $taskDetails['start_on'] ?? $task->start_date,
                ];

                // Оновлюємо виконавця
                if (isset($taskDetails['assignee']['gid'])) {
                    $user = \App\Models\User::where('asana_gid', $taskDetails['assignee']['gid'])->first();
                    if ($user) {
                        $updateData['user_id'] = $user->id;
                    }
                }

                $task->update($updateData);

                // Синхронізуємо кастомні поля
                if (! empty($taskDetails['custom_fields'])) {
                    $this->syncCustomFields($task, $taskDetails['custom_fields']);
                }

                $synced++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Помилка синхронізації задачі', [
                    'task_id' => $task->id,
                    'gid' => $task->gid,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Синхронізовано: {$synced}");
        if ($errors > 0) {
            $this->error("❌ Помилок: {$errors}");
        }

        return self::SUCCESS;
    }

    /**
     * Синхронізувати кастомні поля задачі.
     */
    protected function syncCustomFields(Task $task, array $customFields): void
    {
        foreach ($customFields as $customField) {
            $asanaGid = $customField['gid'] ?? null;
            if (! $asanaGid) {
                continue;
            }

            $projectCustomField = \App\Models\ProjectCustomField::where('project_id', $task->project_id)
                ->where('asana_gid', $asanaGid)
                ->first();

            \App\Models\TaskCustomField::updateOrCreate(
                [
                    'task_id' => $task->id,
                    'asana_gid' => $asanaGid,
                ],
                [
                    'project_custom_field_id' => $projectCustomField?->id,
                    'name' => $customField['name'] ?? '',
                    'type' => $customField['type'] ?? 'text',
                    'text_value' => $customField['text_value'] ?? null,
                    'number_value' => $customField['number_value'] ?? null,
                    'date_value' => $customField['date_value'] ?? null,
                    'enum_value_gid' => isset($customField['enum_value']['gid']) ? (string) $customField['enum_value']['gid'] : null,
                    'enum_value_name' => $customField['enum_value']['name'] ?? null,
                ]
            );
        }
    }
}
