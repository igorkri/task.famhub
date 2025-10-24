# Artisan команды для Asana интеграции

Этот файл содержит готовые Artisan команды для автоматизации синхронизации с Asana.

## Команда синхронизации

Создайте файл `app/Console/Commands/AsanaSyncCommand.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\AsanaSyncProjectsJob;
use App\Jobs\AsanaSyncTasksJob;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;

class AsanaSyncCommand extends Command
{
    protected $signature = 'asana:sync 
                            {--projects : Синхронизировать только проекты}
                            {--tasks : Синхронизировать только задачи}
                            {--all : Полная синхронизация (по умолчанию)}
                            {--stats : Показать статистику после синхронизации}';
                            
    protected $description = 'Синхронизация проектов и задач с Asana';

    public function handle(): int
    {
        $this->info('🚀 Запуск синхронизации с Asana...');
        
        try {
            if ($this->option('all') || (!$this->option('projects') && !$this->option('tasks'))) {
                $this->syncAll();
            } elseif ($this->option('projects')) {
                $this->syncProjects();
            } elseif ($this->option('tasks')) {
                $this->syncTasks();
            }
            
            if ($this->option('stats')) {
                $this->showStats();
            }
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Ошибка синхронизации: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function syncAll(): void
    {
        $this->info('🔄 Полная синхронизация с Asana...');
        $this->syncProjects();
        $this->syncTasks();
        $this->info('✅ Полная синхронизация завершена!');
    }

    private function syncProjects(): void
    {
        $this->info('📁 Синхронизация проектов...');
        
        $beforeCount = Project::whereNotNull('asana_id')->count();
        
        (new AsanaSyncProjectsJob)->handle();
        
        $afterCount = Project::whereNotNull('asana_id')->count();
        $newProjects = $afterCount - $beforeCount;
        
        if ($newProjects > 0) {
            $this->info("✅ Добавлено новых проектов: {$newProjects}");
        }
        $this->info("📊 Всего проектов синхронизировано: {$afterCount}");
    }

    private function syncTasks(): void
    {
        $this->info('📋 Синхронизация задач...');
        
        $beforeCount = Task::whereNotNull('gid')->count();
        
        (new AsanaSyncTasksJob)->handle();
        
        $afterCount = Task::whereNotNull('gid')->count();
        $newTasks = $afterCount - $beforeCount;
        
        if ($newTasks > 0) {
            $this->info("✅ Добавлено новых задач: {$newTasks}");
        }
        $this->info("📊 Всего задач синхронизировано: {$afterCount}");
    }

    private function showStats(): void
    {
        $this->info('📊 Статистика синхронизации:');
        
        $workspaces = Workspace::whereNotNull('gid')->count();
        $projects = Project::whereNotNull('asana_id')->count();
        $tasks = Task::whereNotNull('gid')->count();
        $completedTasks = Task::whereNotNull('gid')->where('is_completed', true)->count();
        
        $this->table(['Элемент', 'Количество'], [
            ['Workspaces (Asana)', $workspaces],
            ['Проекты (Asana)', $projects],
            ['Задачи (Asana)', $tasks],
            ['Завершенные задачи', $completedTasks],
        ]);
        
        if ($tasks > 0) {
            $completionRate = round(($completedTasks / $tasks) * 100, 2);
            $this->info("📈 Процент завершения: {$completionRate}%");
        }
    }
}
```

## Команда мониторинга

Создайте файл `app/Console/Commands/AsanaStatusCommand.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AsanaService;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;

class AsanaStatusCommand extends Command
{
    protected $signature = 'asana:status 
                            {--detailed : Показать детальную статистику}
                            {--check-connection : Проверить подключение к Asana}';
                            
    protected $description = 'Показать статус синхронизации с Asana';

    public function handle(): int
    {
        $this->info('📊 Статус интеграции с Asana');
        $this->line('');
        
        if ($this->option('check-connection')) {
            $this->checkConnection();
        }
        
        $this->showBasicStats();
        
        if ($this->option('detailed')) {
            $this->showDetailedStats();
        }
        
        return self::SUCCESS;
    }

    private function checkConnection(): void
    {
        $this->info('🔌 Проверка подключения к Asana...');
        
        try {
            $asanaService = app(AsanaService::class);
            $workspaceId = config('services.asana.workspace_id');
            
            if (empty($workspaceId)) {
                $this->error('❌ ASANA_WORKSPACE_ID не настроен');
                return;
            }
            
            $projects = $asanaService->getWorkspaceProjects($workspaceId);
            $this->info("✅ Подключение успешно! Доступно проектов в Asana: " . count($projects));
            
        } catch (\Exception $e) {
            $this->error('❌ Ошибка подключения: ' . $e->getMessage());
        }
        
        $this->line('');
    }

    private function showBasicStats(): void
    {
        $stats = [
            ['Элемент', 'Локально', 'Из Asana', 'Синхронизировано'],
            [
                'Workspaces',
                Workspace::count(),
                Workspace::whereNotNull('gid')->count(),
                Workspace::whereNotNull('gid')->count() > 0 ? '✅' : '❌'
            ],
            [
                'Проекты',
                Project::count(),
                Project::whereNotNull('asana_id')->count(),
                Project::whereNotNull('asana_id')->count() > 0 ? '✅' : '❌'
            ],
            [
                'Задачи',
                Task::count(),
                Task::whereNotNull('gid')->count(),
                Task::whereNotNull('gid')->count() > 0 ? '✅' : '❌'
            ]
        ];
        
        $this->table($stats[0], array_slice($stats, 1));
    }

    private function showDetailedStats(): void
    {
        $this->line('');
        $this->info('📋 Детальная статистика по проектам:');
        
        $projects = Project::whereNotNull('asana_id')
            ->withCount([
                'tasks as total_tasks' => function($query) {
                    $query->whereNotNull('gid');
                },
                'tasks as completed_tasks' => function($query) {
                    $query->whereNotNull('gid')->where('is_completed', true);
                }
            ])
            ->orderBy('total_tasks', 'desc')
            ->take(10)
            ->get();
        
        if ($projects->isEmpty()) {
            $this->warn('⚠️ Нет синхронизированных проектов');
            return;
        }
        
        $tableData = [];
        foreach ($projects as $project) {
            $progress = $project->total_tasks > 0 
                ? round(($project->completed_tasks / $project->total_tasks) * 100, 1) 
                : 0;
                
            $tableData[] = [
                $project->name,
                $project->total_tasks,
                $project->completed_tasks,
                $progress . '%'
            ];
        }
        
        $this->table(['Проект', 'Всего задач', 'Завершено', 'Прогресс'], $tableData);
    }
}
```

## Команда очистки

Создайте файл `app/Console/Commands/AsanaCleanCommand.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;

class AsanaCleanCommand extends Command
{
    protected $signature = 'asana:clean 
                            {--projects : Очистить только проекты}
                            {--tasks : Очистить только задачи}
                            {--workspaces : Очистить только workspaces}
                            {--all : Очистить все данные Asana}
                            {--force : Выполнить без подтверждения}';
                            
    protected $description = 'Очистка синхронизированных данных Asana';

    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️ Вы уверены, что хотите удалить синхронизированные данные Asana?')) {
                $this->info('Операция отменена');
                return self::SUCCESS;
            }
        }
        
        if ($this->option('all')) {
            $this->cleanAll();
        } else {
            if ($this->option('tasks')) {
                $this->cleanTasks();
            }
            if ($this->option('projects')) {
                $this->cleanProjects();
            }
            if ($this->option('workspaces')) {
                $this->cleanWorkspaces();
            }
        }
        
        return self::SUCCESS;
    }

    private function cleanAll(): void
    {
        $this->info('🧹 Полная очистка данных Asana...');
        $this->cleanTasks();
        $this->cleanProjects();
        $this->cleanWorkspaces();
        $this->info('✅ Полная очистка завершена');
    }

    private function cleanTasks(): void
    {
        $count = Task::whereNotNull('gid')->count();
        if ($count > 0) {
            Task::whereNotNull('gid')->delete();
            $this->info("🗑️ Удалено задач из Asana: {$count}");
        } else {
            $this->info('ℹ️ Нет задач для удаления');
        }
    }

    private function cleanProjects(): void
    {
        $count = Project::whereNotNull('asana_id')->count();
        if ($count > 0) {
            Project::whereNotNull('asana_id')->delete();
            $this->info("🗑️ Удалено проектов из Asana: {$count}");
        } else {
            $this->info('ℹ️ Нет проектов для удаления');
        }
    }

    private function cleanWorkspaces(): void
    {
        $count = Workspace::whereNotNull('gid')->count();
        if ($count > 0) {
            Workspace::whereNotNull('gid')->delete();
            $this->info("🗑️ Удалено workspaces из Asana: {$count}");
        } else {
            $this->info('ℹ️ Нет workspaces для удаления');
        }
    }
}
```

## Регистрация команд

Добавьте команды в `bootstrap/app.php`:

```php
use App\Console\Commands\AsanaSyncCommand;
use App\Console\Commands\AsanaStatusCommand;
use App\Console\Commands\AsanaCleanCommand;

->withCommands([
    AsanaSyncCommand::class,
    AsanaStatusCommand::class,
    AsanaCleanCommand::class,
])
```

## Использование команд

### Синхронизация

```bash
# Полная синхронизация
php artisan asana:sync --all --stats

# Только проекты
php artisan asana:sync --projects

# Только задачи  
php artisan asana:sync --tasks

# С показом статистики
php artisan asana:sync --stats
```

### Мониторинг

```bash
# Базовая статистика
php artisan asana:status

# Детальная статистика
php artisan asana:status --detailed

# Проверка подключения
php artisan asana:status --check-connection

# Все вместе
php artisan asana:status --detailed --check-connection
```

### Очистка

```bash
# Очистка с подтверждением
php artisan asana:clean --all

# Принудительная очистка
php artisan asana:clean --all --force

# Очистка только задач
php artisan asana:clean --tasks --force

# Очистка только проектов
php artisan asana:clean --projects --force
```

## Настройка Scheduler

В `routes/console.php` добавьте автоматическую синхронизацию:

```php
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\AsanaSyncCommand;

Schedule::command('asana:sync --projects')->daily();
Schedule::command('asana:sync --tasks')->everyThirtyMinutes();
Schedule::command('asana:status')->hourly();
```

## Monitoring скрипт

Создайте файл `scripts/asana-monitor.sh`:

```bash
#!/bin/bash

echo "🔍 Мониторинг Asana интеграции"
echo "==============================="

# Проверка статуса
php artisan asana:status --check-connection

# Синхронизация если есть изменения
echo ""
echo "🔄 Запуск синхронизации..."
php artisan asana:sync --all --stats

# Создание бэкапа статистики
echo ""
echo "💾 Создание отчета..."
php artisan tinker --execute="echo json_encode(['timestamp' => now(), 'projects' => \App\Models\Project::whereNotNull('asana_id')->count(), 'tasks' => \App\Models\Task::whereNotNull('gid')->count()], JSON_PRETTY_PRINT);" > storage/logs/asana_stats_$(date +%Y%m%d_%H%M%S).json

echo "✅ Мониторинг завершен"
```

Сделайте скрипт исполняемым:

```bash
chmod +x scripts/asana-monitor.sh
```

## Пример crontab

```bash
# Автоматическая синхронизация проектов каждый день в 6:00
0 6 * * * cd /path/to/project && php artisan asana:sync --projects

# Синхронизация задач каждые 30 минут в рабочее время
*/30 9-18 * * 1-5 cd /path/to/project && php artisan asana:sync --tasks

# Еженедельный отчет по воскресеньям в 22:00
0 22 * * 0 cd /path/to/project && php artisan asana:status --detailed > /var/log/asana-weekly-report.log

# Мониторинг каждый час
0 * * * * cd /path/to/project && ./scripts/asana-monitor.sh
```

---

**Все команды готовы к использованию после создания соответствующих файлов в директории `app/Console/Commands/`**
