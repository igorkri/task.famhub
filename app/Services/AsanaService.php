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

        $this->client->options['headers']['Asana-Disable'] = 'new_goal_memberships';
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
     * @return array
     */
    public function getWorkspaceProjects(string $workspaceId): array
    {
        $iterator = $this->client->projects->findByWorkspace($workspaceId);
        /** @var array<array{gid: string, name: string, notes: string}> $projects */
        $projects = iterator_to_array($iterator);
        return $projects;
    }

    /**
     * Получить задачи из проекта Asana.
     * @param string $projectId
     * @return array
     */
    public function getProjectTasks(string $projectId): array
    {
        // Используем базовый вызов без дополнительных параметров
        $iterator = $this->client->tasks->findByProject($projectId);
        /** @var array<array{gid: string, name: string}> $tasks */
        $tasks = iterator_to_array($iterator);
        return $tasks;
    }

    /**
     * Получить детальную информацию о задаче.
     * @param string $taskId
     * @return array
     */
    public function getTaskDetails(string $taskId): array
    {
        $task = $this->client->tasks->findById($taskId);
        return (array) $task;
    }
}
