<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Section;
use App\Models\Task;
use App\Models\User;
use App\Services\AsanaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAsanaWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $event) {}

    /**
     * Execute the job.
     */
    public function handle(AsanaService $service): void
    {
        $action = $this->event['action'] ?? null;
        $resource = $this->event['resource'] ?? null;

        if (! $action || ! $resource) {
            Log::warning('Invalid webhook event structure', ['event' => $this->event]);

            return;
        }

        $resourceType = $resource['resource_type'] ?? null;
        $resourceGid = $resource['gid'] ?? null;

        Log::info('Processing Asana webhook', [
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_gid' => $resourceGid,
        ]);

        // Обробка різних типів ресурсів
        match ($resourceType) {
            'task' => $this->handleTaskEvent($action, $resourceGid, $service),
            'project' => $this->handleProjectEvent($action, $resourceGid, $service),
            'story' => $this->handleStoryEvent($action, $resourceGid, $resource, $service),
            'section' => $this->handleSectionEvent($action, $resourceGid, $service),
            default => Log::info('Unhandled resource type', ['type' => $resourceType]),
        };
    }

    /**
     * Handle task events (created, changed, deleted).
     */
    protected function handleTaskEvent(string $action, string $gid, AsanaService $service): void
    {
        if ($action === 'deleted') {
            // Видаляємо таск з бази
            Task::where('gid', $gid)->delete();
            Log::info('Task deleted from webhook', ['gid' => $gid]);

            return;
        }

        if (in_array($action, ['added', 'changed'])) {
            try {
                $taskDetails = $service->getTaskDetails($gid);

                \Log::info('Task details from Asana', [
                    'gid' => $gid,
                    'memberships' => $taskDetails['memberships'] ?? [],
                ]);

                // Знаходимо проект
                $project = null;
                if (! empty($taskDetails['memberships'])) {
                    foreach ($taskDetails['memberships'] as $membership) {
                        \Log::info('Processing membership', [
                            'membership' => $membership,
                            'has_project' => isset($membership['project']),
                            'project_data' => $membership['project'] ?? null,
                        ]);

                        if (isset($membership['project']['gid'])) {
                            $project = Project::where('asana_id', $membership['project']['gid'])->first();
                            if ($project) {
                                \Log::info('Found project', [
                                    'project_id' => $project->id,
                                    'asana_id' => $project->asana_id,
                                ]);
                                break;
                            } else {
                                \Log::warning('Project not found in database', [
                                    'asana_project_gid' => $membership['project']['gid'],
                                ]);
                            }
                        }
                    }
                } else {
                    \Log::warning('No memberships found in task details', [
                        'gid' => $gid,
                        'task_details' => $taskDetails,
                    ]);
                }

                // Знаходимо секцію
                $section = null;
                if (! empty($taskDetails['memberships'])) {
                    foreach ($taskDetails['memberships'] as $membership) {
                        if (isset($membership['section']['gid'])) {
                            $section = Section::where('asana_gid', $membership['section']['gid'])->first();
                            if ($section) {
                                break;
                            }
                        }
                    }
                }

                // Знаходимо або створюємо користувача
                $userId = null;
                if (! empty($taskDetails['assignee'])) {
                    $assignee = $taskDetails['assignee'];
                    $userData = ['name' => $assignee['name'] ?? 'Unknown'];

                    if (! empty($assignee['email'])) {
                        $userData['email'] = $assignee['email'];
                    }

                    $user = User::updateOrCreate(
                        ['asana_gid' => $assignee['gid']],
                        $userData
                    );
                    $userId = $user->id;
                }

                // Визначаємо статус на основі секції
                $status = 'new';
                if ($section && $section->status) {
                    $status = $section->status;
                }

                // Перевіряємо, чи таск вже існує
                $existingTask = Task::where('gid', $gid)->first();

                if ($existingTask) {
                    // Оновлюємо існуючий таск (зберігаємо існуючі значення, якщо нових немає)
                    \Log::info('Before update - checking variables', [
                        'gid' => $gid,
                        'project_is_null' => is_null($project),
                        'project_id' => $project?->id ?? 'NULL',
                        'existing_project_id' => $existingTask->project_id,
                        'section_is_null' => is_null($section),
                        'userId_is_null' => is_null($userId),
                    ]);

                    Task::withoutEvents(function () use ($gid, $taskDetails, $project, $section, $userId, $existingTask) {
                        $updateData = [
                            'title' => $taskDetails['name'] ?? $existingTask->title,
                            'description' => $taskDetails['notes'] ?? $existingTask->description,
                            'is_completed' => $taskDetails['completed'] ?? $existingTask->is_completed,
                            'deadline' => $taskDetails['due_on'] ?? $existingTask->deadline,
                        ];

                        // Оновлюємо project_id тільки якщо знайшли проєкт
                        if ($project) {
                            $updateData['project_id'] = $project->id;
                        } elseif (! $existingTask->project_id) {
                            // Якщо у таску немає project_id взагалі, логуємо помилку
                            Log::warning('Task has no project in Asana and no project_id in database', [
                                'gid' => $gid,
                                'task_name' => $taskDetails['name'] ?? '',
                            ]);

                            return; // Не оновлюємо таск без проєкту
                        }
                        // Інакше зберігаємо існуючий project_id

                        if ($section) {
                            $updateData['section_id'] = $section->id;
                            $updateData['status'] = $section->status ?? $existingTask->status;
                        }

                        if ($userId) {
                            $updateData['user_id'] = $userId;
                        }

                        \Log::info('Update data prepared', [
                            'gid' => $gid,
                            'updateData' => $updateData,
                        ]);

                        $existingTask->update($updateData);

                        // Синхронізуємо кастомні поля
                        if (! empty($taskDetails['custom_fields'])) {
                            foreach ($taskDetails['custom_fields'] as $customField) {
                                \App\Models\TaskCustomField::updateOrCreate(
                                    [
                                        'task_id' => $existingTask->id,
                                        'asana_gid' => $customField['gid'],
                                    ],
                                    [
                                        'name' => $customField['name'],
                                        'type' => $customField['type'],
                                        'text_value' => $customField['text_value'] ?? null,
                                        'number_value' => $customField['number_value'] ?? null,
                                        'enum_value_gid' => $customField['enum_value']['gid'] ?? null,
                                        'enum_value_name' => $customField['enum_value']['name'] ?? null,
                                    ]
                                );
                            }
                        }

                        Log::info('Task updated from webhook', [
                            'gid' => $gid,
                            'updated_fields' => array_keys($updateData),
                            'custom_fields_count' => count($taskDetails['custom_fields'] ?? []),
                        ]);
                    });
                } else {
                    // Створюємо новий таск - project_id обов'язковий
                    if (! $project) {
                        Log::error('Webhook sync error: project not found for new task', [
                            'task_gid' => $gid,
                            'task_name' => $taskDetails['name'] ?? '',
                            'memberships' => $taskDetails['memberships'] ?? [],
                        ]);

                        return;
                    }

                    Task::withoutEvents(function () use ($gid, $taskDetails, $project, $section, $userId, $status) {
                        $newTask = Task::create([
                            'gid' => $gid,
                            'title' => $taskDetails['name'] ?? '',
                            'description' => $taskDetails['notes'] ?? '',
                            'project_id' => $project->id,
                            'section_id' => $section?->id,
                            'user_id' => $userId,
                            'status' => $status,
                            'is_completed' => $taskDetails['completed'] ?? false,
                            'deadline' => $taskDetails['due_on'] ?? null,
                        ]);

                        // Синхронізуємо кастомні поля
                        if (! empty($taskDetails['custom_fields'])) {
                            foreach ($taskDetails['custom_fields'] as $customField) {
                                \App\Models\TaskCustomField::create([
                                    'task_id' => $newTask->id,
                                    'asana_gid' => $customField['gid'],
                                    'name' => $customField['name'],
                                    'type' => $customField['type'],
                                    'text_value' => $customField['text_value'] ?? null,
                                    'number_value' => $customField['number_value'] ?? null,
                                    'enum_value_gid' => $customField['enum_value']['gid'] ?? null,
                                    'enum_value_name' => $customField['enum_value']['name'] ?? null,
                                ]);
                            }
                        }

                        Log::info('Task created from webhook', [
                            'gid' => $gid,
                            'title' => $taskDetails['name'] ?? '',
                            'project_id' => $project->id,
                            'custom_fields_count' => count($taskDetails['custom_fields'] ?? []),
                        ]);
                    });
                }
            } catch (\Exception $e) {
                Log::error('Error processing task event', [
                    'gid' => $gid,
                    'action' => $action,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle project events (created, changed, deleted).
     */
    protected function handleProjectEvent(string $action, string $gid, AsanaService $service): void
    {
        if ($action === 'deleted') {
            // Видаляємо проект з бази
            Project::where('asana_id', $gid)->delete();
            Log::info('Project deleted from webhook', ['gid' => $gid]);

            return;
        }

        if (in_array($action, ['added', 'changed'])) {
            try {
                $projectDetails = $service->getProjectDetails($gid);

                \Log::info('Project details from Asana', [
                    'gid' => $gid,
                    'team' => $projectDetails['team'] ?? null,
                ]);

                // Знаходимо команду
                $teamId = null;
                if (! empty($projectDetails['team']['gid'])) {
                    $team = Team::where('asana_gid', $projectDetails['team']['gid'])->first();
                    if ($team) {
                        $teamId = $team->id;
                    } else {
                        \Log::warning('Team not found in database', [
                            'asana_team_gid' => $projectDetails['team']['gid'],
                        ]);
                    }
                }

                // Оновлюємо або створюємо проект
                Project::updateOrCreate(
                    ['asana_id' => $gid],
                    [
                        'name' => $projectDetails['name'] ?? '',
                        'description' => $projectDetails['notes'] ?? '',
                        'team_id' => $teamId,
                        'asana_data' => $projectDetails,
                    ]
                );

                Log::info('Project synced from webhook', [
                    'gid' => $gid,
                    'action' => $action,
                ]);
            } catch (\Exception $e) {
                Log::error('Error processing project event', [
                    'gid' => $gid,
                    'action' => $action,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle story events (created, changed, deleted).
     */
    protected function handleStoryEvent(string $action, string $gid, array $resource, AsanaService $service): void
    {
        // Stories in Asana are a type of task, we might not need separate handling
        Log::info('Story event received', [
            'action' => $action,
            'gid' => $gid,
        ]);

        $this->handleTaskEvent($action, $gid, $service);
    }

    /**
     * Handle section events (created, changed, deleted).
     */
    protected function handleSectionEvent(string $action, string $gid, AsanaService $service): void
    {
        if ($action === 'deleted') {
            // Видаляємо секцію з бази
            Section::where('asana_gid', $gid)->delete();
            Log::info('Section deleted from webhook', ['gid' => $gid]);

            return;
        }

        if (in_array($action, ['added', 'changed'])) {
            try {
                $sectionDetails = $service->getSectionDetails($gid);

                \Log::info('Section details from Asana', [
                    'gid' => $gid,
                ]);

                // Оновлюємо або створюємо секцію
                Section::updateOrCreate(
                    ['asana_gid' => $gid],
                    [
                        'name' => $sectionDetails['name'] ?? '',
                        'asana_data' => $sectionDetails,
                    ]
                );

                Log::info('Section synced from webhook', [
                    'gid' => $gid,
                    'action' => $action,
                ]);
            } catch (\Exception $e) {
                Log::error('Error processing section event', [
                    'gid' => $gid,
                    'action' => $action,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }
}
