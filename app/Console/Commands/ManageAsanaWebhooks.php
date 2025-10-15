<?php

namespace App\Console\Commands;

use App\Services\AsanaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ManageAsanaWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'asana:webhooks
                            {action : Action to perform: list, create, delete, delete-all}
                            {--resource= : Resource GID (required for create)}
                            {--webhook= : Webhook GID (required for delete)}
                            {--url= : Webhook target URL (optional, uses APP_URL if not provided)}';

    /**
     * The console command description.
     */
    protected $description = 'Manage Asana webhooks (list, create, delete)';

    /**
     * Execute the console command.
     */
    public function handle(AsanaService $service): int
    {
        $action = $this->argument('action');
        $workspaceId = config('services.asana.workspace_id');

        if (! $workspaceId) {
            $this->error('ASANA_WORKSPACE_ID не налаштовано в .env');

            return self::FAILURE;
        }

        return match ($action) {
            'list' => $this->listWebhooks($service, $workspaceId),
            'create' => $this->createWebhook($service),
            'delete' => $this->deleteWebhook($service),
            'delete-all' => $this->deleteAllWebhooks($service, $workspaceId),
            default => $this->error("Невідома команда: {$action}. Використовуйте: list, create, delete, delete-all"),
        };
    }

    /**
     * List all webhooks.
     */
    protected function listWebhooks(AsanaService $service, string $workspaceId): int
    {
        $this->info('Отримую список webhooks...');

        try {
            $webhooks = $service->getWebhooks($workspaceId);

            if (empty($webhooks)) {
                $this->warn('Webhooks не знайдено');

                return self::SUCCESS;
            }

            $this->table(
                ['GID', 'Resource', 'Target', 'Active'],
                collect($webhooks)->map(fn ($webhook) => [
                    $webhook['gid'],
                    $webhook['resource']['name'].' ('.$webhook['resource']['gid'].')',
                    $webhook['target'],
                    $webhook['active'] ? '✓' : '✗',
                ])
            );

            $this->info('Знайдено webhooks: '.count($webhooks));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Помилка при отриманні webhooks: '.$e->getMessage());
            Log::error('Failed to list webhooks', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }

    /**
     * Create new webhook.
     */
    protected function createWebhook(AsanaService $service): int
    {
        $resourceId = $this->option('resource');
        $url = $this->option('url') ?? config('app.url').'/api/webhooks/asana';

        if (! $resourceId) {
            $this->error('Потрібно вказати --resource=GID (project, portfolio, або workspace GID)');

            return self::FAILURE;
        }

        $this->info("Створюю webhook для ресурсу {$resourceId}...");
        $this->info("Target URL: {$url}");

        try {
            $webhook = $service->createWebhook($resourceId, $url);

            // Зберігаємо webhook в базі даних
            \App\Models\AsanaWebhook::updateOrCreate(
                ['gid' => $webhook['gid']],
                [
                    'resource_type' => $webhook['resource']['resource_type'] ?? 'unknown',
                    'resource_gid' => $webhook['resource']['gid'],
                    'resource_name' => $webhook['resource']['name'],
                    'target' => $webhook['target'],
                    'active' => $webhook['active'],
                ]
            );

            $this->info('✓ Webhook успішно створено!');
            $this->line("  GID: {$webhook['gid']}");
            $this->line("  Resource: {$webhook['resource']['name']} ({$webhook['resource']['gid']})");
            $this->line("  Target: {$webhook['target']}");
            $this->line('  Active: '.($webhook['active'] ? 'Yes' : 'No'));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Помилка при створенні webhook: '.$e->getMessage());
            Log::error('Failed to create webhook', [
                'resource' => $resourceId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Delete webhook.
     */
    protected function deleteWebhook(AsanaService $service): int
    {
        $webhookId = $this->option('webhook');

        if (! $webhookId) {
            $this->error('Потрібно вказати --webhook=GID');

            return self::FAILURE;
        }

        if (! $this->confirm("Ви впевнені, що хочете видалити webhook {$webhookId}?")) {
            $this->info('Скасовано');

            return self::SUCCESS;
        }

        $this->info("Видаляю webhook {$webhookId}...");

        try {
            $service->deleteWebhook($webhookId);

            // Видаляємо з БД
            \App\Models\AsanaWebhook::where('gid', $webhookId)->delete();

            $this->info('✓ Webhook успішно видалено!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Помилка при видаленні webhook: '.$e->getMessage());
            Log::error('Failed to delete webhook', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Delete all webhooks.
     */
    protected function deleteAllWebhooks(AsanaService $service, string $workspaceId): int
    {
        try {
            $webhooks = $service->getWebhooks($workspaceId);

            if (empty($webhooks)) {
                $this->warn('Webhooks не знайдено');

                return self::SUCCESS;
            }

            $this->table(
                ['GID', 'Resource', 'Target'],
                collect($webhooks)->map(fn ($webhook) => [
                    $webhook['gid'],
                    $webhook['resource']['name'],
                    $webhook['target'],
                ])
            );

            if (! $this->confirm('Видалити ВСІ ці webhooks?')) {
                $this->info('Скасовано');

                return self::SUCCESS;
            }

            $bar = $this->output->createProgressBar(count($webhooks));
            $bar->start();

            foreach ($webhooks as $webhook) {
                try {
                    $service->deleteWebhook($webhook['gid']);
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->warn("Не вдалося видалити webhook {$webhook['gid']}: ".$e->getMessage());
                }
            }

            $bar->finish();
            $this->newLine(2);
            $this->info('✓ Всі webhooks видалено!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Помилка: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
