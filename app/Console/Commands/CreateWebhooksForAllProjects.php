<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\AsanaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateWebhooksForAllProjects extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'asana:webhooks:create-all
                            {--url= : Webhook target URL (optional, uses APP_URL if not provided)}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Create Asana webhooks for all projects that have asana_id';

    /**
     * Execute the console command.
     */
    public function handle(AsanaService $service): int
    {
        $url = $this->option('url') ?? config('app.url').'/api/webhooks/asana';

        // Отримуємо всі проекти з asana_id
        $projects = Project::whereNotNull('asana_id')->get();

        if ($projects->isEmpty()) {
            $this->warn('Проекти з asana_id не знайдено');

            return self::SUCCESS;
        }

        $this->info("Знайдено проектів: {$projects->count()}");
        $this->info("Target URL: {$url}");
        $this->newLine();

        // Показуємо список проектів
        $this->table(
            ['ID', 'Назва', 'Asana ID'],
            $projects->map(fn ($p) => [$p->id, $p->name, $p->asana_id])
        );

        if (! $this->option('force') && ! $this->confirm('Створити webhooks для всіх цих проектів?', true)) {
            $this->info('Скасовано');

            return self::SUCCESS;
        }

        $this->newLine();
        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        $created = 0;
        $errors = 0;
        $errorDetails = [];

        foreach ($projects as $project) {
            try {
                $webhook = $service->createWebhook($project->asana_id, $url);

                // Зберігаємо webhook в базі даних
                \App\Models\AsanaWebhook::updateOrCreate(
                    ['gid' => $webhook['gid']],
                    [
                        'resource_type' => $webhook['resource']['resource_type'] ?? 'project',
                        'resource_gid' => $webhook['resource']['gid'],
                        'resource_name' => $webhook['resource']['name'],
                        'target' => $webhook['target'],
                        'active' => $webhook['active'],
                    ]
                );

                $created++;
            } catch (\Exception $e) {
                $errors++;
                $errorDetails[] = [
                    'project' => $project->name,
                    'asana_id' => $project->asana_id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to create webhook for project', [
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
        $this->info("✓ Успішно створено: {$created}");

        if ($errors > 0) {
            $this->warn("✗ Помилок: {$errors}");
            $this->newLine();

            if (! empty($errorDetails)) {
                $this->error('Деталі помилок:');
                foreach ($errorDetails as $detail) {
                    $this->line("  • {$detail['project']} ({$detail['asana_id']}): {$detail['error']}");
                }
            }
        }

        $this->newLine();

        if ($created > 0) {
            $this->info('✓ Webhooks успішно створено!');
            $this->line('Перевірити список: php artisan asana:webhooks list');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
