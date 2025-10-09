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
        if (!isset($this->client->options)) {
            $this->client->options = [];
        }
        if (!isset($this->client->options['headers'])) {
            $this->client->options['headers'] = [];
        }

        $this->client->options['headers']['Asana-Disable'] = 'new_goal_memberships,new_user_task_lists';
    }

    /**
     * Получить проекты пользователя из Asana.
     * @return array
     */
    public function getProjects(): array
    {
        // Можно добавить параметры, если нужно
        $result = $this->client->projects->findAll();
        return $result['data'] ?? [];
    }

    /**
     * Получить проекты из конкретного workspace Asana.
     * @param string $workspaceId
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
     * @param string $projectId
     * @return array<array{gid: string, name: string, notes: string, completed: bool, due_on: string|null}>
     */
    public function getProjectTasks(string $projectId): array
    {
        /** @var \Traversable<array{gid: string, name: string, notes: string, completed: bool, due_on: string|null}> $iterator */
        $iterator = $this->client->tasks->findByProject($projectId, [
            'opt_fields' => 'gid,name,notes,completed,due_on',
        ]);
        $tasks = iterator_to_array($iterator);
        return $tasks;
    }

    /**
     * Получить детальную информацию о задаче.
     * @param string $taskId
     * @return array{gid: string, name: string, notes: string, completed: bool, due_on: string|null}
     */
    public function getTaskDetails(string $taskId): array
    {
        $task = $this->client->tasks->findById($taskId, [
            'opt_fields' => 'gid,name,notes,completed,due_on',
        ]);
        return (array) $task;
    }

    /**
     * Обновить данные задачи в Asana.
     * @param string $taskId
     * @param array $data
     * @return array
     */
    public function updateTask(string $taskId, array $data): array
    {
        $response = $this->client->tasks->update($taskId, $data);


        return (array) $response;
    }
}
