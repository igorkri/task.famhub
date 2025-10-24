<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectCustomField;
use App\Models\TaskCustomField;
use App\Services\AsanaService;
use Illuminate\Console\Command;

class ManageCustomFields extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'custom-fields:manage
                            {action? : show, sync-project, sync-tasks, clear}';

    /**
     * The console command description.
     */
    protected $description = 'Управління кастомними полями Asana';

    /**
     * Execute the console command.
     */
    public function handle(AsanaService $service): int
    {
        $action = $this->argument('action');

        if (! $action) {
            $action = $this->choice(
                'Що хочете зробити?',
                [
                    'show' => '📊 Показати поточні кастомні поля',
                    'sync-project' => '🔄 Синхронізувати поля проєктів з Asana',
                    'sync-tasks' => '🔄 Синхронізувати значення полів тасків',
                    'clear' => '🗑️  Очистити всі кастомні поля',
                    'exit' => '❌ Вихід',
                ],
                'show'
            );
        }

        return match ($action) {
            'show' => $this->showCustomFields($service),
            'sync-project' => $this->syncProjectFields($service),
            'sync-tasks' => $this->syncTaskFields(),
            'clear' => $this->clearCustomFields(),
            'exit' => self::SUCCESS,
            default => $this->error("Невідома команда: {$action}"),
        };
    }

    /**
     * Показати поточні кастомні поля.
     */
    protected function showCustomFields(AsanaService $service): int
    {
        $this->info('📊 КАСТОМНІ ПОЛЯ В БАЗІ ДАНИХ');
        $this->newLine();

        $projects = Project::whereNotNull('asana_id')->get();

        if ($projects->isEmpty()) {
            $this->warn('Проєкти не знайдено');

            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            $this->line("📁 <fg=cyan>{$project->name}</> (ID: {$project->id})");

            // Поля проєкту (налаштування)
            $projectFields = ProjectCustomField::where('project_id', $project->id)->get();

            if ($projectFields->isEmpty()) {
                $this->line('   <fg=yellow>⚠ Немає синхронізованих полів</>');
                $this->line('   <fg=gray>Запустіть: php artisan custom-fields:manage sync-project</>');
                $this->newLine();

                continue;
            }

            $this->line('   <fg=green>Налаштування полів:</>');
            foreach ($projectFields as $field) {
                $icon = match ($field->type) {
                    'enum' => '📋',
                    'number' => '🔢',
                    'text' => '📝',
                    'date' => '📅',
                    default => '⚙️',
                };
                $this->line("   {$icon} {$field->name} <fg=gray>({$field->type})</>");

                // Для enum показати варіанти
                if ($field->type === 'enum' && ! empty($field->enum_options)) {
                    foreach ($field->enum_options as $option) {
                        $this->line("      • {$option['name']}");
                    }
                }
            }

            // Статистика значень
            $valuesCount = TaskCustomField::whereHas('task', function ($q) use ($project) {
                $q->where('project_id', $project->id);
            })->count();

            if ($valuesCount > 0) {
                $this->line("   <fg=green>✓ Синхронізовано значень: {$valuesCount}</>");
            } else {
                $this->line('   <fg=yellow>⚠ Значення для тасків не синхронізовано</>');
                $this->line('   <fg=gray>Запустіть: php artisan custom-fields:manage sync-tasks</>');
            }

            $this->newLine();
        }

        // Загальна статистика
        $this->info('📈 ЗАГАЛЬНА СТАТИСТИКА');
        $this->table(
            ['Показник', 'Кількість'],
            [
                ['Проєктів', $projects->count()],
                ['Налаштувань полів', ProjectCustomField::count()],
                ['Значень в тасках', TaskCustomField::count()],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Синхронізувати поля проєктів з Asana.
     */
    protected function syncProjectFields(AsanaService $service): int
    {
        $this->info('🔄 Синхронізація кастомних полів проєктів з Asana...');
        $this->newLine();

        $projects = Project::whereNotNull('asana_id')->get();

        if ($projects->isEmpty()) {
            $this->warn('Проєкти не знайдено');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        $synced = 0;
        $totalFields = 0;

        foreach ($projects as $project) {
            try {
                $fields = $service->getProjectCustomFields($project->asana_id);

                foreach ($fields as $field) {
                    ProjectCustomField::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'asana_gid' => $field['gid'],
                        ],
                        [
                            'name' => $field['name'],
                            'type' => $field['type'],
                            'description' => $field['description'],
                            'enum_options' => $field['enum_options'],
                            'is_required' => $field['is_required'],
                            'precision' => $field['precision'],
                        ]
                    );
                    $totalFields++;
                }

                $synced++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Помилка для проєкту {$project->name}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Синхронізовано проєктів: {$synced}");
        $this->info("✅ Синхронізовано полів: {$totalFields}");

        if ($totalFields > 0) {
            $this->newLine();
            $this->line('💡 Тепер синхронізуйте значення для тасків:');
            $this->line('   <fg=cyan>php artisan custom-fields:manage sync-tasks</>');
        }

        return self::SUCCESS;
    }

    /**
     * Синхронізувати значення полів тасків.
     */
    protected function syncTaskFields(): int
    {
        $this->info('🔄 Синхронізація значень кастомних полів тасків...');
        $this->newLine();

        // Перевірка чи є налаштування полів
        $fieldsCount = ProjectCustomField::count();
        if ($fieldsCount === 0) {
            $this->warn('⚠️  Спочатку синхронізуйте налаштування полів проєктів!');
            $this->line('   <fg=cyan>php artisan custom-fields:manage sync-project</>');

            return self::FAILURE;
        }

        // Делегуємо існуючій команді
        $this->call('asana:sync-custom-fields');

        return self::SUCCESS;
    }

    /**
     * Очистити всі кастомні поля.
     */
    protected function clearCustomFields(): int
    {
        if (! $this->confirm('⚠️  Ви впевнені? Це видалить ВСІ кастомні поля з бази даних!', false)) {
            $this->info('Скасовано');

            return self::SUCCESS;
        }

        $taskFieldsCount = TaskCustomField::count();
        $projectFieldsCount = ProjectCustomField::count();

        TaskCustomField::truncate();
        ProjectCustomField::truncate();

        $this->info("✅ Видалено значень тасків: {$taskFieldsCount}");
        $this->info("✅ Видалено налаштувань проєктів: {$projectFieldsCount}");

        return self::SUCCESS;
    }
}
