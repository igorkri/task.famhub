# Примеры использования Asana Integration

## Базовые примеры

### 1. Проверка подключения к Asana

```php
// Тест соединения
try {
    $asanaService = app(\App\Services\AsanaService::class);
    $workspaceId = config('services.asana.workspace_id');
    $projects = $asanaService->getWorkspaceProjects($workspaceId);
    
    echo "✅ Подключение успешно! Найдено проектов: " . count($projects);
} catch (\Exception $e) {
    echo "❌ Ошибка подключения: " . $e->getMessage();
}
```

### 2. Полная синхронизация (проекты + задачи)

```php
// Скрипт полной синхронизации
function fullAsanaSync() {
    echo "🔄 Начинаем полную синхронизацию с Asana...\n";
    
    // 1. Синхронизация проектов
    echo "📁 Синхронизация проектов...\n";
    $projectsJob = new \App\Jobs\AsanaSyncProjectsJob();
    $projectsJob->handle();
    
    $projectsCount = \App\Models\Project::whereNotNull('asana_id')->count();
    echo "✅ Синхронизировано проектов: {$projectsCount}\n";
    
    // 2. Синхронизация задач
    echo "📋 Синхронизация задач...\n";
    $tasksJob = new \App\Jobs\AsanaSyncTasksJob();
    $tasksJob->handle();
    
    $tasksCount = \App\Models\Task::whereNotNull('gid')->count();
    echo "✅ Синхронизировано задач: {$tasksCount}\n";
    
    echo "🎉 Полная синхронизация завершена!\n";
    
    return [
        'projects' => $projectsCount,
        'tasks' => $tasksCount
    ];
}

// Запуск
$result = fullAsanaSync();
```

### 3. Статистика синхронизации

```php
function getAsanaSyncStats() {
    $stats = [
        'workspaces' => [
            'total' => \App\Models\Workspace::count(),
            'with_asana_gid' => \App\Models\Workspace::whereNotNull('gid')->count()
        ],
        'projects' => [
            'total' => \App\Models\Project::count(),
            'synced_from_asana' => \App\Models\Project::whereNotNull('asana_id')->count()
        ],
        'tasks' => [
            'total' => \App\Models\Task::count(),
            'synced_from_asana' => \App\Models\Task::whereNotNull('gid')->count(),
            'completed' => \App\Models\Task::where('is_completed', true)->whereNotNull('gid')->count()
        ]
    ];
    
    echo "📊 Статистика синхронизации Asana:\n";
    echo "Workspaces: {$stats['workspaces']['with_asana_gid']}/{$stats['workspaces']['total']}\n";
    echo "Projects: {$stats['projects']['synced_from_asana']}/{$stats['projects']['total']}\n";
    echo "Tasks: {$stats['tasks']['synced_from_asana']}/{$stats['tasks']['total']}\n";
    echo "Completed tasks: {$stats['tasks']['completed']}\n";
    
    return $stats;
}

// Использование
$stats = getAsanaSyncStats();
```

## Работа с проектами

### 4. Получение проектов из конкретного workspace

```php
function getAsanaProjects() {
    $asanaService = app(\App\Services\AsanaService::class);
    $workspaceId = config('services.asana.workspace_id');
    
    $projects = $asanaService->getWorkspaceProjects($workspaceId);
    
    echo "📁 Проекты в Asana workspace:\n";
    foreach ($projects as $project) {
        echo "- {$project->name} (ID: {$project->gid})\n";
    }
    
    return $projects;
}

// Использование
$asanaProjects = getAsanaProjects();
```

### 5. Синхронизация конкретного проекта

```php
function syncSpecificProject($asanaProjectId) {
    $asanaService = app(\App\Services\AsanaService::class);
    $workspaceId = config('services.asana.workspace_id');
    
    // Найдем или создадим workspace
    $workspace = \App\Models\Workspace::firstOrCreate(
        ['gid' => $workspaceId],
        ['name' => 'Asana Workspace', 'description' => 'Рабочее пространство из Asana']
    );
    
    // Получаем информацию о проекте из Asana
    $asanaProjects = $asanaService->getWorkspaceProjects($workspaceId);
    $targetProject = collect($asanaProjects)->firstWhere('gid', $asanaProjectId);
    
    if (!$targetProject) {
        throw new \Exception("Проект с ID {$asanaProjectId} не найден в Asana");
    }
    
    // Создаем/обновляем проект
    $project = \App\Models\Project::updateOrCreate(
        ['asana_id' => $targetProject->gid],
        [
            'name' => $targetProject->name,
            'description' => '',
            'workspace_id' => $workspace->id,
        ]
    );
    
    echo "✅ Проект '{$project->name}' синхронизирован (ID: {$project->id})\n";
    
    // Синхронизируем задачи этого проекта
    $tasks = $asanaService->getProjectTasks($asanaProjectId);
    $tasksCreated = 0;
    
    foreach ($tasks as $asanaTask) {
        \App\Models\Task::updateOrCreate(
            ['gid' => $asanaTask->gid],
            [
                'title' => $asanaTask->name,
                'project_id' => $project->id,
                'description' => '',
                'status' => 'new',
                'is_completed' => false,
            ]
        );
        $tasksCreated++;
    }
    
    echo "✅ Синхронизировано задач: {$tasksCreated}\n";
    
    return $project;
}

// Использование
$project = syncSpecificProject('1202674268244535');
```

## Работа с задачами

### 6. Получение задач проекта

```php
function getProjectTasks($projectId) {
    $project = \App\Models\Project::findOrFail($projectId);
    
    if (!$project->asana_id) {
        throw new \Exception('Проект не синхронизирован с Asana');
    }
    
    $asanaService = app(\App\Services\AsanaService::class);
    $asanaTasks = $asanaService->getProjectTasks($project->asana_id);
    
    echo "📋 Задачи проекта '{$project->name}' в Asana:\n";
    foreach ($asanaTasks as $task) {
        echo "- {$task->name} (ID: {$task->gid})\n";
    }
    
    // Получаем локальные задачи
    $localTasks = $project->tasks()->whereNotNull('gid')->get();
    echo "\n💾 Синхронизированные задачи в БД: {$localTasks->count()}\n";
    
    return [
        'asana_tasks' => $asanaTasks,
        'local_tasks' => $localTasks
    ];
}

// Использование
$tasks = getProjectTasks(1);
```

### 7. Анализ различий между Asana и локальной БД

```php
function compareAsanaWithLocal() {
    $asanaService = app(\App\Services\AsanaService::class);
    $differences = [];
    
    $projects = \App\Models\Project::whereNotNull('asana_id')->get();
    
    foreach ($projects as $project) {
        $asanaTasks = $asanaService->getProjectTasks($project->asana_id);
        $localTasks = $project->tasks()->whereNotNull('gid')->pluck('gid')->toArray();
        
        $asanaTaskIds = collect($asanaTasks)->pluck('gid')->toArray();
        
        $missingInLocal = array_diff($asanaTaskIds, $localTasks);
        $extraInLocal = array_diff($localTasks, $asanaTaskIds);
        
        if (!empty($missingInLocal) || !empty($extraInLocal)) {
            $differences[$project->name] = [
                'missing_in_local' => count($missingInLocal),
                'extra_in_local' => count($extraInLocal),
                'asana_tasks' => count($asanaTaskIds),
                'local_tasks' => count($localTasks)
            ];
        }
    }
    
    if (empty($differences)) {
        echo "✅ Все проекты синхронизированы корректно!\n";
    } else {
        echo "⚠️ Найдены различия:\n";
        foreach ($differences as $projectName => $diff) {
            echo "📁 {$projectName}:\n";
            echo "  - В Asana: {$diff['asana_tasks']} задач\n";
            echo "  - В БД: {$diff['local_tasks']} задач\n";
            echo "  - Отсутствует в БД: {$diff['missing_in_local']}\n";
            echo "  - Лишние в БД: {$diff['extra_in_local']}\n\n";
        }
    }
    
    return $differences;
}

// Использование
$differences = compareAsanaWithLocal();
```

## Расширенные примеры

### 8. Мониторинг изменений

```php
function monitorAsanaChanges() {
    echo "🔍 Мониторинг изменений в Asana...\n";
    
    // Запоминаем текущее состояние
    $beforeSync = [
        'projects' => \App\Models\Project::whereNotNull('asana_id')->count(),
        'tasks' => \App\Models\Task::whereNotNull('gid')->count()
    ];
    
    // Выполняем синхронизацию
    $projectsJob = new \App\Jobs\AsanaSyncProjectsJob();
    $projectsJob->handle();
    
    $tasksJob = new \App\Jobs\AsanaSyncTasksJob();
    $tasksJob->handle();
    
    // Проверяем изменения
    $afterSync = [
        'projects' => \App\Models\Project::whereNotNull('asana_id')->count(),
        'tasks' => \App\Models\Task::whereNotNull('gid')->count()
    ];
    
    $changes = [
        'projects_added' => $afterSync['projects'] - $beforeSync['projects'],
        'tasks_added' => $afterSync['tasks'] - $beforeSync['tasks']
    ];
    
    echo "📊 Результаты мониторинга:\n";
    echo "Новых проектов: {$changes['projects_added']}\n";
    echo "Новых задач: {$changes['tasks_added']}\n";
    
    if ($changes['projects_added'] > 0 || $changes['tasks_added'] > 0) {
        echo "🔄 Обнаружены изменения!\n";
    } else {
        echo "✅ Изменений не обнаружено\n";
    }
    
    return $changes;
}

// Использование
$changes = monitorAsanaChanges();
```

### 9. Создание отчета

```php
function generateAsanaReport() {
    $report = [
        'sync_date' => now()->format('Y-m-d H:i:s'),
        'workspace' => null,
        'projects' => [],
        'totals' => [
            'projects_count' => 0,
            'tasks_count' => 0,
            'completed_tasks' => 0
        ]
    ];
    
    // Информация о workspace
    $workspace = \App\Models\Workspace::whereNotNull('gid')->first();
    if ($workspace) {
        $report['workspace'] = [
            'name' => $workspace->name,
            'gid' => $workspace->gid
        ];
    }
    
    // Информация о проектах
    $projects = \App\Models\Project::whereNotNull('asana_id')
        ->withCount([
            'tasks as total_tasks' => function($query) {
                $query->whereNotNull('gid');
            },
            'tasks as completed_tasks' => function($query) {
                $query->whereNotNull('gid')->where('is_completed', true);
            }
        ])
        ->orderBy('total_tasks', 'desc')
        ->get();
    
    foreach ($projects as $project) {
        $report['projects'][] = [
            'name' => $project->name,
            'asana_id' => $project->asana_id,
            'total_tasks' => $project->total_tasks,
            'completed_tasks' => $project->completed_tasks,
            'progress' => $project->total_tasks > 0 
                ? round(($project->completed_tasks / $project->total_tasks) * 100, 2) 
                : 0
        ];
        
        $report['totals']['tasks_count'] += $project->total_tasks;
        $report['totals']['completed_tasks'] += $project->completed_tasks;
    }
    
    $report['totals']['projects_count'] = count($report['projects']);
    
    // Сохраняем отчет
    $reportJson = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $filename = 'asana_report_' . now()->format('Y_m_d_H_i_s') . '.json';
    file_put_contents(storage_path("app/{$filename}"), $reportJson);
    
    echo "📋 Отчет создан: {$filename}\n";
    echo "📊 Статистика:\n";
    echo "- Проектов: {$report['totals']['projects_count']}\n";
    echo "- Задач: {$report['totals']['tasks_count']}\n";
    echo "- Завершено: {$report['totals']['completed_tasks']}\n";
    
    return $report;
}

// Использование
$report = generateAsanaReport();
```

### 10. Очистка и пересинхронизация

```php
function cleanAndResync() {
    echo "🧹 Очистка данных Asana...\n";
    
    // Удаляем синхронизированные задачи
    $deletedTasks = \App\Models\Task::whereNotNull('gid')->delete();
    echo "🗑️ Удалено задач: {$deletedTasks}\n";
    
    // Удаляем синхронизированные проекты
    $deletedProjects = \App\Models\Project::whereNotNull('asana_id')->delete();
    echo "🗑️ Удалено проектов: {$deletedProjects}\n";
    
    // Удаляем workspace с Asana GID
    $deletedWorkspaces = \App\Models\Workspace::whereNotNull('gid')->delete();
    echo "🗑️ Удалено workspaces: {$deletedWorkspaces}\n";
    
    echo "\n🔄 Начинаем полную пересинхронизацию...\n";
    
    // Полная синхронизация
    fullAsanaSync();
    
    echo "✅ Пересинхронизация завершена!\n";
}

// Использование (ВНИМАНИЕ: удаляет все данные Asana!)
// cleanAndResync();
```

## Команды для быстрого использования

### Artisan команды

Создайте файл `app/Console/Commands/AsanaSyncCommand.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\AsanaSyncProjectsJob;
use App\Jobs\AsanaSyncTasksJob;

class AsanaSyncCommand extends Command
{
    protected $signature = 'asana:sync {--projects} {--tasks} {--all}';
    protected $description = 'Синхронизация данных с Asana';

    public function handle()
    {
        if ($this->option('all') || (!$this->option('projects') && !$this->option('tasks'))) {
            $this->syncAll();
        } elseif ($this->option('projects')) {
            $this->syncProjects();
        } elseif ($this->option('tasks')) {
            $this->syncTasks();
        }
    }

    private function syncAll()
    {
        $this->info('🔄 Полная синхронизация с Asana...');
        $this->syncProjects();
        $this->syncTasks();
        $this->info('✅ Полная синхронизация завершена!');
    }

    private function syncProjects()
    {
        $this->info('📁 Синхронизация проектов...');
        (new AsanaSyncProjectsJob)->handle();
        $count = \App\Models\Project::whereNotNull('asana_id')->count();
        $this->info("✅ Синхронизировано проектов: {$count}");
    }

    private function syncTasks()
    {
        $this->info('📋 Синхронизация задач...');
        (new AsanaSyncTasksJob)->handle();
        $count = \App\Models\Task::whereNotNull('gid')->count();
        $this->info("✅ Синхронизировано задач: {$count}");
    }
}
```

Использование команд:

```bash
# Полная синхронизация
php artisan asana:sync --all

# Только проекты
php artisan asana:sync --projects

# Только задачи
php artisan asana:sync --tasks
```

---

**Все примеры готовы к использованию в Laravel tinker или в пользовательских скриптах.**
