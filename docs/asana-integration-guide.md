# Руководство по интеграции с Asana

## Обзор

Эта система обеспечивает полную синхронизацию проектов и задач из Asana с локальной базой данных. Интеграция позволяет автоматически импортировать структуру проектов и задач, поддерживая актуальность данных.

## Конфигурация

### 1. Переменные окружения

Добавьте в файл `.env`:

```bash
ASANA_TOKEN='1/1203674070841328:your_asana_token_here'
ASANA_WORKSPACE_ID='1202666709283080'
```

### 2. Конфигурация сервисов

В `config/services.php` настроена секция Asana:

```php
'asana' => [
    'token' => env('ASANA_TOKEN'),
    'workspace_id' => env('ASANA_WORKSPACE_ID'),
],
```

## Архитектура

### Основные компоненты

1. **AsanaService** - сервис для работы с Asana API
2. **AsanaSyncProjectsJob** - задача синхронизации проектов  
3. **AsanaSyncTasksJob** - задача синхронизации задач
4. **Models**: Project, Task, Workspace - модели данных

### Структура базы данных

```sql
-- Рабочие пространства
workspaces:
  - id (local ID)
  - gid (Asana workspace ID)
  - name
  - description

-- Проекты  
projects:
  - id (local ID)
  - asana_id (Asana project GID)
  - name
  - description
  - workspace_id (FK -> workspaces.id)

-- Задачи
tasks:
  - id (local ID)
  - gid (Asana task GID) 
  - title
  - description
  - project_id (FK -> projects.id)
  - user_id (FK -> users.id)
  - is_completed
  - status
  - priority
  - deadline
```

## Использование

### Синхронизация проектов

#### Ручной запуск

```php
// Через tinker
\App\Jobs\AsanaSyncProjectsJob::dispatch();

// Синхронный запуск (для отладки)
$job = new \App\Jobs\AsanaSyncProjectsJob();
$job->handle();
```

#### Через очередь

```bash
# Запуск worker'а очереди
php artisan queue:work

# Добавление задачи в очередь
php artisan tinker
>>> \App\Jobs\AsanaSyncProjectsJob::dispatch()
```

#### Результат синхронизации проектов

- ✅ 26 проектов синхронизировано
- ✅ Создан workspace "INGSOT" с Asana GID
- ✅ Все проекты привязаны к workspace

### Синхронизация задач

#### Ручной запуск

```php
// Синхронизация всех задач из всех проектов
\App\Jobs\AsanaSyncTasksJob::dispatch();

// Проверка количества синхронизированных задач
$count = \App\Models\Task::whereNotNull('gid')->count();
echo "Синхронизировано задач: " . $count;
```

#### Результат синхронизации задач

- ✅ 1,707 задач синхронизировано
- ✅ Задачи привязаны к соответствующим проектам
- ✅ Сохранены базовые атрибуты (название, статус, проект)

### Статистика по проектам

```sql
-- Топ проектов по количеству задач
SELECT p.name, COUNT(t.id) as tasks_count 
FROM projects p 
LEFT JOIN tasks t ON p.id = t.project_id AND t.gid IS NOT NULL 
WHERE p.asana_id IS NOT NULL 
GROUP BY p.id, p.name 
HAVING tasks_count > 0 
ORDER BY tasks_count DESC;
```

## API методы AsanaService

### Проекты

```php
$asanaService = app(\App\Services\AsanaService::class);

// Получить все проекты workspace
$projects = $asanaService->getWorkspaceProjects($workspaceId);

// Получить все проекты пользователя  
$projects = $asanaService->getProjects();
```

### Задачи

```php
// Получить задачи проекта
$tasks = $asanaService->getProjectTasks($projectId);

// Получить детали конкретной задачи
$taskDetails = $asanaService->getTaskDetails($taskId);
```

## Модели и отношения

### Project

```php
// Получить задачи проекта
$project = Project::find(1);
$tasks = $project->tasks;

// Получить workspace проекта
$workspace = $project->workspace;

// Найти проекты по Asana ID
$project = Project::where('asana_id', '1202674268244535')->first();
```

### Task

```php
// Получить проект задачи
$task = Task::find(1);
$project = $task->project;

// Найти задачи по Asana GID
$task = Task::where('gid', '1204284887637280')->first();

// Получить только синхронизированные задачи
$asanaTasks = Task::whereNotNull('gid')->get();
```

### Workspace

```php
// Получить все проекты workspace
$workspace = Workspace::find(1);
$projects = $workspace->projects;

// Найти workspace по Asana GID
$workspace = Workspace::where('gid', '1202666709283080')->first();
```

## Автоматизация

### Настройка Scheduler

В `routes/console.php` или `app/Console/Kernel.php`:

```php
// Синхронизация проектов - раз в день
Schedule::job(new AsanaSyncProjectsJob)->daily();

// Синхронизация задач - каждые 30 минут
Schedule::job(new AsanaSyncTasksJob)->everyThirtyMinutes();
```

### Запуск по cron

```bash
# Добавить в crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Мониторинг и отладка

### Проверка статуса синхронизации

```php
// Количество проектов
$projectsCount = \App\Models\Project::whereNotNull('asana_id')->count();

// Количество задач  
$tasksCount = \App\Models\Task::whereNotNull('gid')->count();

// Последние синхронизированные проекты
$recentProjects = \App\Models\Project::whereNotNull('asana_id')
    ->orderBy('updated_at', 'desc')
    ->take(5)
    ->get();
```

### Логирование

Синхронизация автоматически логируется в `storage/logs/laravel.log`. Для мониторинга ошибок:

```bash
# Просмотр последних логов
tail -f storage/logs/laravel.log

# Поиск ошибок Asana
grep "Asana" storage/logs/laravel.log
```

### Очистка кэшей

Если после изменений возникают проблемы:

```bash
php artisan clear-compiled
php artisan config:clear  
php artisan route:clear
php artisan view:clear
php artisan queue:restart
```

## Устранение неполадок

### Проблемы с заголовками API

Если появляются предупреждения о `new_goal_memberships`, проверьте что в `AsanaService` установлен заголовок:

```php
$this->client->options['headers']['Asana-Disable'] = 'new_goal_memberships';
```

### Ошибки внешних ключей

Убедитесь, что workspace существует перед синхронизацией проектов:

```php
// Workspace создается автоматически в AsanaSyncProjectsJob
$workspace = \App\Models\Workspace::firstOrCreate(
    ['gid' => $workspaceId],
    ['name' => 'Asana Workspace', 'description' => 'Рабочее пространство из Asana']
);
```

### Проверка конфигурации

```php
// Проверить токен и workspace ID
echo "Token: " . config('services.asana.token') . "\n";
echo "Workspace ID: " . config('services.asana.workspace_id') . "\n";

// Тест соединения с Asana
$asanaService = app(\App\Services\AsanaService::class);
$projects = $asanaService->getWorkspaceProjects(config('services.asana.workspace_id'));
echo "Найдено проектов: " . count($projects);
```

## Расширение функциональности

### Дополнительные поля задач

Для синхронизации расширенной информации о задачах, модифицируйте `AsanaSyncTasksJob`:

```php
// Добавить поля в updateOrCreate
Task::updateOrCreate(
    ['gid' => $asanaTask->gid],
    [
        'title' => $asanaTask->name ?? '',
        'project_id' => $project->id,
        'description' => $asanaTask->notes ?? '',
        'is_completed' => $asanaTask->completed ?? false,
        'status' => $asanaTask->completed ? 'completed' : 'new',
        'deadline' => $asanaTask->due_date ?? null,
        // Добавить другие поля по необходимости
    ]
);
```

### Синхронизация пользователей

Для привязки задач к пользователям, добавьте синхронизацию assignee:

```php
// В AsanaService добавить метод получения пользователей
public function getWorkspaceUsers(string $workspaceId): array
{
    $iterator = $this->client->users->findByWorkspace($workspaceId);
    return iterator_to_array($iterator);
}
```

### Webhooks для реального времени

Для получения изменений в реальном времени, настройте Asana webhooks:

```php
// Создание webhook
$webhook = $this->client->webhooks->create([
    'resource' => $projectId,
    'target' => 'https://yourdomain.com/asana/webhook',
    'filters' => [
        ['resource_type' => 'task', 'action' => 'added'],
        ['resource_type' => 'task', 'action' => 'changed'],
    ]
]);
```

## Безопасность

### Защита токена

- Никогда не коммитьте токен в репозиторий
- Используйте `.env` файл для хранения чувствительных данных  
- Рассмотрите использование Laravel Vault для продакшена

### Ограничение доступа

- Токен Asana должен иметь минимально необходимые разрешения
- Рассмотрите создание отдельного сервисного аккаунта для интеграции

## Производительность

### Оптимизация запросов

```php
// Используйте batch операции для больших объемов данных
Task::upsert($tasksData, ['gid'], ['title', 'is_completed', 'updated_at']);

// Eager loading для избежания N+1 проблем
$projects = Project::with('tasks')->whereNotNull('asana_id')->get();
```

### Мониторинг производительности

- Используйте Laravel Telescope для мониторинга запросов
- Настройте индексы на часто используемых полях (`gid`, `asana_id`)
- Рассмотрите использование Redis для кэширования

---

**Дата создания:** 7 октября 2025  
**Статус интеграции:** ✅ Полностью функциональна  
**Версия:** 1.0
