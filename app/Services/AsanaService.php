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
        /** @var \Traversable<\stdClass> $iterator */
        $iterator = $this->client->projects->findByWorkspace($workspaceId);
        $projects = iterator_to_array($iterator);

        // Конвертируем объекты в массивы
        return array_map(function ($project) {
            return [
                'gid' => $project->gid ?? '',
                'name' => $project->name ?? '',
                'notes' => $project->notes ?? '',
            ];
        }, $projects);
    }

    /**
     * Получить задачи из проекта Asana.
     *
     * @return array<array{gid: string, name: string, notes: string, completed: bool, due_on: string|null, assignee: array{name: string, email: string}|null, memberships: array}>
     */
    public function getProjectTasks(string $projectId): array
    {
        /** @var \Traversable<\stdClass> $iterator */
        $iterator = $this->client->tasks->findByProject($projectId, [
            'opt_fields' => 'gid,name,notes,completed,due_on,assignee.name,assignee.email,memberships.section',
        ]);
        $tasks = iterator_to_array($iterator);

        // Конвертируем объекты в массивы
        return array_map(function ($task) {
            $result = [
                'gid' => $task->gid ?? '',
                'name' => $task->name ?? '',
                'notes' => $task->notes ?? '',
                'completed' => (bool) ($task->completed ?? false),
                'due_on' => $task->due_on ?? null,
                'assignee' => null,
                'memberships' => [],
            ];

            // Обрабатываем assignee
            if (isset($task->assignee) && $task->assignee) {
                $result['assignee'] = [
                    'name' => $task->assignee->name ?? '',
                    'email' => $task->assignee->email ?? '',
                ];
            }

            // Обрабатываем memberships
            if (isset($task->memberships) && is_array($task->memberships)) {
                foreach ($task->memberships as $membership) {
                    $membershipData = [
                        'section' => null,
                        'project' => null,
                    ];

                    if (isset($membership->section) && $membership->section) {
                        $membershipData['section'] = [
                            'gid' => $membership->section->gid ?? '',
                            'name' => $membership->section->name ?? '',
                        ];
                    }

                    if (isset($membership->project) && $membership->project) {
                        $membershipData['project'] = [
                            'gid' => $membership->project->gid ?? '',
                            'name' => $membership->project->name ?? '',
                        ];
                    }

                    $result['memberships'][] = $membershipData;
                }
            }

            return $result;
        }, $tasks);
    }

    /**
     * Получить секции проекта из Asana.
     *
     * @return array<array{gid: string, name: string}>
     */
    public function getProjectSections(string $projectId): array
    {
        /** @var \Traversable<\stdClass> $iterator */
        $iterator = $this->client->sections->findByProject($projectId);
        $sections = iterator_to_array($iterator);

        // Конвертируем объекты в массивы
        return array_map(function ($section) {
            return [
                'gid' => $section->gid ?? '',
                'name' => $section->name ?? '',
            ];
        }, $sections);
    }

    /**
     * Получить детальную информацию о задаче.
     *
     * @return array{gid: string, name: string, notes: string, completed: bool, due_on: string|null, start_on: string|null, assignee: array|null, memberships: array, custom_fields: array}
     */
    public function getTaskDetails(string $taskId): array
    {
        $task = $this->client->tasks->findById($taskId, [
            'opt_fields' => 'gid,name,notes,completed,due_on,start_on,assignee.gid,assignee.name,assignee.email,memberships.section.gid,memberships.project.gid,memberships.project.name,custom_fields,created_at,modified_at',
        ]);

        // Логируем raw ответ для отладки
        \Log::info('Raw Asana API response for task', [
            'task_gid' => $taskId,
            'raw_response' => json_encode($task),
        ]);

        // Конвертируем stdClass в массив правильным образом
        $result = [
            'gid' => $task->gid ?? '',
            'name' => $task->name ?? '',
            'notes' => $task->notes ?? '',
            'completed' => (bool) ($task->completed ?? false),
            'due_on' => $task->due_on ?? null,
            'start_on' => $task->start_on ?? null,
            'created_at' => $task->created_at ?? null,
            'modified_at' => $task->modified_at ?? null,
            'assignee' => null,
            'memberships' => [],
            'custom_fields' => [],
        ];

        // Обрабатываем assignee
        if (isset($task->assignee) && $task->assignee) {
            $result['assignee'] = [
                'gid' => $task->assignee->gid ?? '',
                'name' => $task->assignee->name ?? '',
                'email' => $task->assignee->email ?? '',
            ];
        }

        // Обрабатываем memberships
        if (isset($task->memberships) && is_array($task->memberships)) {
            foreach ($task->memberships as $membership) {
                $membershipData = [
                    'section' => null,
                    'project' => null,
                ];

                if (isset($membership->section) && $membership->section) {
                    $membershipData['section'] = [
                        'gid' => $membership->section->gid ?? '',
                        'name' => $membership->section->name ?? '',
                    ];
                }

                if (isset($membership->project) && $membership->project) {
                    $membershipData['project'] = [
                        'gid' => $membership->project->gid ?? '',
                        'name' => $membership->project->name ?? '',
                    ];
                }

                $result['memberships'][] = $membershipData;
            }
        }

        // Обрабатываем custom_fields
        if (isset($task->custom_fields) && is_array($task->custom_fields)) {
            foreach ($task->custom_fields as $field) {
                $fieldData = [
                    'gid' => $field->gid ?? '',
                    'name' => $field->name ?? '',
                    'type' => $field->type ?? '',
                    'enum_value' => null,
                    'number_value' => $field->number_value ?? null,
                    'text_value' => $field->text_value ?? null,
                ];

                if (isset($field->enum_value) && $field->enum_value) {
                    $fieldData['enum_value'] = [
                        'gid' => $field->enum_value->gid ?? '',
                        'name' => $field->enum_value->name ?? '',
                    ];
                }

                $result['custom_fields'][] = $fieldData;
            }
        }

        return $result;
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

    /**
     * Получить комментарии задачи из Asana.
     *
     * @return array<array{gid: string, text: string, created_by: array, created_at: string}>
     */
    public function getTaskComments(string $taskId): array
    {
        /** @var \Traversable<\stdClass> $iterator */
        $iterator = $this->client->stories->findByTask($taskId, [
            'opt_fields' => 'gid,text,created_by.name,created_by.email,created_at,type',
        ]);
        $stories = iterator_to_array($iterator);

        // Фильтруем только комментарии (тип comment) и конвертируем в массивы
        $comments = array_filter($stories, function ($story) {
            return isset($story->type) && $story->type === 'comment' && ! empty($story->text);
        });

        // Конвертируем объекты в массивы
        return array_map(function ($story) {
            return [
                'gid' => $story->gid ?? null,
                'text' => $story->text ?? '',
                'created_by' => [
                    'name' => $story->created_by->name ?? '',
                    'email' => $story->created_by->email ?? '',
                ],
                'created_at' => $story->created_at ?? null,
                'type' => $story->type ?? '',
            ];
        }, $comments);
    }

    /**
     * Добавить комментарий к задаче в Asana.
     *
     * @return array{gid: string, text: string, created_by: array, created_at: string}
     */
    public function addCommentToTask(string $taskId, string $text): array
    {
        $response = $this->client->stories->createOnTask($taskId, [
            'text' => $text,
        ]);

        return (array) $response;
    }

    /**
     * Создать новую задачу в Asana.
     *
     * @param  array  $data  Данные задачи (name, notes, projects, due_on, completed, parent и т.д.)
     * @return array{gid: string, name: string, notes: string, completed: bool, due_on: string|null, projects: array, memberships: array}
     */
    public function createTask(array $data): array
    {
        // Создаём задачу через Asana API
        $response = $this->client->tasks->create($data);

        // Преобразуем объект ответа в массив
        $task = (array) $response;

        // Формируем структурированный результат
        $result = [
            'gid' => $task['gid'] ?? '',
            'name' => $task['name'] ?? '',
            'notes' => $task['notes'] ?? '',
            'completed' => (bool) ($task['completed'] ?? false),
            'due_on' => $task['due_on'] ?? null,
            'start_on' => $task['start_on'] ?? null,
            'created_at' => $task['created_at'] ?? null,
            'modified_at' => $task['modified_at'] ?? null,
            'projects' => [],
            'memberships' => [],
            'parent' => null,
        ];

        // Обрабатываем проекты
        if (isset($task['projects']) && is_array($task['projects'])) {
            foreach ($task['projects'] as $project) {
                if (is_object($project)) {
                    $result['projects'][] = [
                        'gid' => $project->gid ?? '',
                        'name' => $project->name ?? '',
                    ];
                }
            }
        }

        // Обрабатываем memberships (включая секции)
        if (isset($task['memberships']) && is_array($task['memberships'])) {
            foreach ($task['memberships'] as $membership) {
                $membershipData = [
                    'project' => null,
                    'section' => null,
                ];

                if (is_object($membership)) {
                    if (isset($membership->project)) {
                        $membershipData['project'] = [
                            'gid' => $membership->project->gid ?? '',
                            'name' => $membership->project->name ?? '',
                        ];
                    }

                    if (isset($membership->section)) {
                        $membershipData['section'] = [
                            'gid' => $membership->section->gid ?? '',
                            'name' => $membership->section->name ?? '',
                        ];
                    }
                }

                $result['memberships'][] = $membershipData;
            }
        }

        // Обрабатываем родительскую задачу
        if (isset($task['parent']) && is_object($task['parent'])) {
            $result['parent'] = [
                'gid' => $task['parent']->gid ?? '',
                'name' => $task['parent']->name ?? '',
            ];
        }

        return $result;
    }

    /**
     * Отримати деталі story (коментаря).
     *
     * @return array{gid: string, type: string, text: string, created_by: array}
     */
    public function getStoryDetails(string $storyId): array
    {
        $story = $this->client->stories->findById($storyId);

        return [
            'gid' => $story->gid ?? '',
            'type' => $story->resource_subtype ?? '',
            'text' => $story->text ?? '',
            'created_by' => [
                'gid' => $story->created_by->gid ?? '',
                'name' => $story->created_by->name ?? '',
                'email' => $story->created_by->email ?? '',
            ],
        ];
    }

    /**
     * Створити webhook для ресурсу.
     *
     * @param  string  $resourceId  GID проекту, портфоліо або workspace
     * @param  string  $target  URL для отримання webhooks
     * @return array{gid: string, resource: array, target: string, active: bool}
     */
    public function createWebhook(string $resourceId, string $target): array
    {
        $webhook = $this->client->webhooks->create([
            'resource' => $resourceId,
            'target' => $target,
        ]);

        return [
            'gid' => $webhook->gid ?? '',
            'resource' => [
                'gid' => $webhook->resource->gid ?? '',
                'name' => $webhook->resource->name ?? '',
            ],
            'target' => $webhook->target ?? '',
            'active' => $webhook->active ?? false,
        ];
    }

    /**
     * Отримати всі webhooks для workspace.
     *
     * @return array<array{gid: string, resource: array, target: string, active: bool}>
     */
    public function getWebhooks(string $workspaceId): array
    {
        $webhooks = $this->client->webhooks->getAll([
            'workspace' => $workspaceId,
        ]);

        $result = [];
        foreach ($webhooks as $webhook) {
            $result[] = [
                'gid' => $webhook->gid ?? '',
                'resource' => [
                    'gid' => $webhook->resource->gid ?? '',
                    'name' => $webhook->resource->name ?? '',
                ],
                'target' => $webhook->target ?? '',
                'active' => $webhook->active ?? false,
            ];
        }

        return $result;
    }

    /**
     * Видалити webhook.
     */
    public function deleteWebhook(string $webhookId): void
    {
        $this->client->webhooks->deleteById($webhookId);
    }
}
