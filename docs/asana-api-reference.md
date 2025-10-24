# API Справочник Asana Integration

## AsanaService API

### Конструктор

```php
$asanaService = app(\App\Services\AsanaService::class);
// или
$asanaService = new \App\Services\AsanaService();
```

### Методы для работы с проектами

#### getWorkspaceProjects(string $workspaceId): array

Получает все проекты из указанного рабочего пространства Asana.

**Параметры:**
- `$workspaceId` - ID рабочего пространства Asana

**Возвращает:**
- Массив объектов проектов с полями: `gid`, `name`, `resource_type`

**Пример:**
```php
$workspaceId = config('services.asana.workspace_id');
$projects = $asanaService->getWorkspaceProjects($workspaceId);

foreach ($projects as $project) {
    echo "Проект: {$project->name} (ID: {$project->gid})\n";
}
```

#### getProjects(): array

Получает все проекты текущего пользователя.

**Возвращает:**
- Массив проектов пользователя

**Пример:**
```php
$userProjects = $asanaService->getProjects();
```

### Методы для работы с задачами

#### getProjectTasks(string $projectId): array

Получает все задачи из указанного проекта.

**Параметры:**
- `$projectId` - GID проекта в Asana

**Возвращает:**
- Массив объектов задач с полями: `gid`, `name`, `resource_type`

**Пример:**
```php
$projectId = '1202674268244535';
$tasks = $asanaService->getProjectTasks($projectId);

echo "Задач в проекте: " . count($tasks) . "\n";
foreach ($tasks as $task) {
    echo "- {$task->name} (ID: {$task->gid})\n";
}
```

#### getTaskDetails(string $taskId): array

Получает детальную информацию о конкретной задаче.

**Параметры:**
- `$taskId` - GID задачи в Asana

**Возвращает:**
- Массив с детальной информацией о задаче

**Пример:**
```php
$taskId = '1204284887637280';
$taskDetails = $asanaService->getTaskDetails($taskId);

print_r($taskDetails);
```

## Jobs (Задачи очереди)

### AsanaSyncProjectsJob

Синхронизирует проекты из Asana workspace в локальную базу данных.

#### Запуск

```php
// Через диспетчер очереди
\App\Jobs\AsanaSyncProjectsJob::dispatch();

// Синхронный запуск (для отладки)
$job = new \App\Jobs\AsanaSyncProjectsJob();
$job->handle();
```

#### Что делает

1. Получает `workspace_id` из конфигурации
2. Находит или создает workspace в локальной БД
3. Получает все проекты из Asana workspace
4. Создает/обновляет проекты в таблице `projects`

#### Результат

```php
// Проверка результата
$projectsCount = \App\Models\Project::whereNotNull('asana_id')->count();
echo "Синхронизировано проектов: " . $projectsCount;
```

### AsanaSyncTasksJob

Синхронизирует задачи из всех проектов Asana.

#### Запуск

```php
// Через диспетчер очереди
\App\Jobs\AsanaSyncTasksJob::dispatch();

// Синхронный запуск
$job = new \App\Jobs\AsanaSyncTasksJob();
$job->handle();
```

#### Что делает

1. Получает все проекты с `asana_id` из локальной БД
2. Для каждого проекта получает задачи из Asana
3. Создает/обновляет задачи в таблице `tasks`

#### Результат

```php
// Проверка результата
$tasksCount = \App\Models\Task::whereNotNull('gid')->count();
echo "Синхронизировано задач: " . $tasksCount;
```

## Модели Eloquent

### Project

#### Свойства

```php
protected $fillable = ['asana_id', 'name', 'description', 'workspace_id'];
```

#### Отношения

```php
// Workspace проекта
$project = Project::find(1);
$workspace = $project->workspace;

// Задачи проекта
$tasks = $project->tasks;
```

#### Методы

```php
// Найти проект по Asana ID
$project = Project::where('asana_id', '1202674268244535')->first();

// Получить все синхронизированные проекты
$asanaProjects = Project::whereNotNull('asana_id')->get();

// Проекты с количеством задач
$projectsWithTasks = Project::withCount('tasks')->get();
```

### Task

#### Свойства

```php
protected $fillable = [
    'gid', 'parent_id', 'project_id', 'user_id', 'title', 
    'description', 'is_completed', 'status', 'priority', 
    'deadline', 'budget', 'spent', 'progress', 'start_date', 'end_date'
];
```

#### Константы статусов

```php
const STATUS_NEW = 'new';
const STATUS_IN_PROGRESS = 'in_progress';
const STATUS_COMPLETED = 'completed';
const STATUS_CANCELED = 'canceled';
const STATUS_NEEDS_CLARIFICATION = 'needs_clarification';
```

#### Константы приоритетов

```php
const PRIORITY_LOW = 'low';
const PRIORITY_MEDIUM = 'medium';
const PRIORITY_HIGH = 'high';
```

#### Отношения

```php
// Проект задачи
$task = Task::find(1);
$project = $task->project;

// Пользователь (исполнитель)
$user = $task->user;

// Родительская задача
$parentTask = $task->parent;

// Подзадачи
$subtasks = $task->subtasks;
```

#### Методы

```php
// Найти задачу по Asana GID
$task = Task::where('gid', '1204284887637280')->first();

// Получить только синхронизированные задачи
$asanaTasks = Task::whereNotNull('gid')->get();

// Завершенные задачи
$completedTasks = Task::where('is_completed', true)->get();

// Задачи проекта
$projectTasks = Task::where('project_id', 1)->get();
```

### Workspace

#### Свойства

```php
protected $fillable = ['gid', 'name', 'description'];
```

#### Отношения

```php
// Проекты workspace
$workspace = Workspace::find(1);
$projects = $workspace->projects;
```

#### Методы

```php
// Найти workspace по Asana GID
$workspace = Workspace::where('gid', '1202666709283080')->first();

// Workspace с количеством проектов
$workspace = Workspace::withCount('projects')->first();
```

## Примеры SQL запросов

### Статистика по проектам

```sql
-- Топ проектов по количеству задач
SELECT 
    p.name,
    p.asana_id,
    COUNT(t.id) as tasks_count,
    SUM(CASE WHEN t.is_completed = 1 THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN t.is_completed = 0 THEN 1 ELSE 0 END) as pending_tasks
FROM projects p 
LEFT JOIN tasks t ON p.id = t.project_id AND t.gid IS NOT NULL 
WHERE p.asana_id IS NOT NULL 
GROUP BY p.id, p.name, p.asana_id
ORDER BY tasks_count DESC;
```

### Анализ задач

```sql
-- Задачи по статусам
SELECT 
    status,
    COUNT(*) as count
FROM tasks 
WHERE gid IS NOT NULL 
GROUP BY status;

-- Задачи по приоритетам
SELECT 
    priority,
    COUNT(*) as count
FROM tasks 
WHERE gid IS NOT NULL 
GROUP BY priority;
```

### Общая статистика

```sql
-- Общая информация о синхронизации
SELECT 
    'Workspaces' as entity,
    COUNT(*) as total,
    SUM(CASE WHEN gid IS NOT NULL THEN 1 ELSE 0 END) as synced
FROM workspaces
UNION ALL
SELECT 
    'Projects' as entity,
    COUNT(*) as total,
    SUM(CASE WHEN asana_id IS NOT NULL THEN 1 ELSE 0 END) as synced
FROM projects
UNION ALL
SELECT 
    'Tasks' as entity,
    COUNT(*) as total,
    SUM(CASE WHEN gid IS NOT NULL THEN 1 ELSE 0 END) as synced
FROM tasks;
```

## Конфигурационные параметры

### Переменные окружения

| Переменная | Описание | Пример |
|------------|----------|---------|
| `ASANA_TOKEN` | Персональный токен доступа к Asana API | `1/1203674070841328:token_here` |
| `ASANA_WORKSPACE_ID` | ID рабочего пространства в Asana | `1202666709283080` |

### Конфигурация services.php

```php
'asana' => [
    'token' => env('ASANA_TOKEN'),
    'workspace_id' => env('ASANA_WORKSPACE_ID'),
],
```

### Получение конфигурации

```php
// Токен
$token = config('services.asana.token');

// Workspace ID
$workspaceId = config('services.asana.workspace_id');

// Проверка конфигурации
if (empty($token) || empty($workspaceId)) {
    throw new \RuntimeException('Asana configuration is missing');
}
```

## Коды ошибок и их решения

### Ошибка: "Cannot use object of type Asana\Iterator\ItemIterator as array"

**Причина:** Попытка работать с итератором Asana как с массивом.

**Решение:**
```php
// Неправильно
foreach ($asanaResponse as $item) { ... }

// Правильно
$items = iterator_to_array($asanaResponse);
foreach ($items as $item) { ... }
```

### Ошибка: "Cannot use object of type stdClass as array"

**Причина:** Обращение к свойствам объекта через синтаксис массива.

**Решение:**
```php
// Неправильно
$name = $asanaProject['name'];

// Правильно
$name = $asanaProject->name;
```

### Ошибка: "Integrity constraint violation: foreign key fails"

**Причина:** Попытка создать проект/задачу со ссылкой на несуществующий workspace/project.

**Решение:**
```php
// Убедиться что workspace существует перед созданием проектов
$workspace = Workspace::firstOrCreate(['gid' => $workspaceId]);

// Использовать локальный ID, а не Asana ID
'workspace_id' => $workspace->id, // не $workspaceId
```

### Ошибка: "new_goal_memberships deprecation"

**Причина:** Asana API предупреждение о новой функциональности.

**Решение:**
```php
// В AsanaService конструкторе
$this->client->options['headers']['Asana-Disable'] = 'new_goal_memberships';
```

---

**Версия API:** 1.0  
**Совместимость:** Laravel 12, Asana API v1.0
