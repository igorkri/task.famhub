<?php

namespace App\Services;

use Asana\Client;

class AsanaService
{
    protected Client $client;

    public function __construct()
    {
        $token = config('services.asana.token');
        $this->client = Client::accessToken($token);

        // Полностью переопределяем опции клиента для подавления предупреждений
        if (! isset($this->client->options)) {
            $this->client->options = [];
        }
        if (! isset($this->client->options['headers'])) {
            $this->client->options['headers'] = [];
        }

        $this->client->options['headers']['Asana-Disable'] = 'new_goal_memberships,new_user_task_lists';
    }

    /**
     * Получить проекты пользователя из Asana.
     */
    public function getProjects(): array
    {
        // Можно добавить параметры, если нужно
        $result = $this->client->projects->findAll();

        return $result['data'] ?? [];
    }

    /**
     * Получить проекты из конкретного workspace Asana.
     *
     * @return array<array{gid: string, name: string, notes: string}>
     */
    public function getWorkspaceProjects(string $workspaceId): array
    {
        /** @var \Traversable<array{gid: string, name: string, notes: string}> $iterator */
        $iterator = $this->client->projects->findByWorkspace($workspaceId);
        $projects = iterator_to_array($iterator);

        return $projects;
    }

    /**
     * Получить задачи из проекта Asana.
     *
     * @return array<array{gid: string, name: string, notes: string, completed: bool, due_on: string|null, assignee: array{name: string, email: string}|null, memberships: array}>
     */
    public function getProjectTasks(string $projectId): array
    {
        /** @var \Traversable<array{gid: string, name: string, notes: string, completed: bool, due_on: string|null, assignee: array|null, memberships: array}> $iterator */
        $iterator = $this->client->tasks->findByProject($projectId, [
            'opt_fields' => 'gid,name,notes,completed,due_on,assignee.name,assignee.email,memberships.section',
        ]);
        $tasks = iterator_to_array($iterator);

        return $tasks;
    }

    /**
     * Получить секции проекта из Asana.
     *
     * @return array<array{gid: string, name: string}>
     */
    public function getProjectSections(string $projectId): array
    {
        /** @var \Traversable<array{gid: string, name: string}> $iterator */
        $iterator = $this->client->sections->findByProject($projectId);
        $sections = iterator_to_array($iterator);

        return $sections;
    }

    /**
     * Получить детальную информацию о задаче.
     *
     * @return array{gid: string, name: string, notes: string, completed: bool, due_on: string|null, start_on: string|null, assignee: array|null, memberships: array}
     */
    public function getTaskDetails(string $taskId): array
    {
        $task = $this->client->tasks->findById($taskId, [
            'opt_fields' => 'gid,name,notes,completed,due_on,start_on,assignee.gid,assignee.name,assignee.email,memberships.section',
        ]);

        return (array) $task;
    }

    /**
     * Обновить данные задачи в Asana.
     */
    public function updateTask(string $taskId, array $data): array
    {
        $response = $this->client->tasks->update($taskId, $data);

        return (array) $response;
    }

    /**
     * Переместить задачу в секцию проекта.
     */
    public function moveTaskToSection(string $taskId, string $sectionId): array
    {
        $response = $this->client->sections->addTask($sectionId, ['task' => $taskId]);

        return (array) $response;
    }
}
