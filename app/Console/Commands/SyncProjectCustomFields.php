<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectCustomField;
use App\Services\AsanaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProjectCustomFields extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'asana:sync-project-custom-fields
                            {--project= : Sync only specific project by ID}
                            {--force : Overwrite existing custom fields}';

    /**
     * The console command description.
     */
    protected $description = 'Sync custom field definitions from Asana for projects';

    /**
     * Execute the console command.
     */
    public function handle(AsanaService $service): int
    {
        $projectId = $this->option('project');
        $force = $this->option('force');

        // Отримуємо проєкти для синхронізації
        $query = Project::query()->whereNotNull('asana_id');

        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->warn('Проєкти для синхронізації не знайдено');

            return self::SUCCESS;
        }

        $this->info("Знайдено проєктів для синхронізації: {$projects->count()}");
        $this->newLine();

        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        $synced = 0;
        $errors = 0;
        $totalFields = 0;

        foreach ($projects as $project) {
            try {
                // Отримуємо кастомні поля проєкту з Asana
                $customFields = $service->getProjectCustomFields($project->asana_id);

                if (empty($customFields)) {
                    $bar->advance();

                    continue;
                }

                // Синхронізуємо кожне кастомне поле
                foreach ($customFields as $field) {
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
                $errors++;
                Log::error('Failed to sync custom fields for project', [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'asana_id' => $project->asana_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Показуємо результати
        $this->info("✓ Синхронізовано проєктів: {$synced}");
        $this->info("✓ Синхронізовано полів: {$totalFields}");

        if ($errors > 0) {
            $this->warn("✗ Помилок: {$errors}");
        }

        $this->newLine();
        $this->info('✓ Синхронізація завершена!');
        $this->line('Тепер запустіть: php artisan asana:sync-custom-fields');

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
