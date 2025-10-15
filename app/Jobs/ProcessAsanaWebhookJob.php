<?php

namespace App\Jobs;

use App\Models\Comment;
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
            // Отримуємо деталі таску з Asana
            try {
                $taskDetails = $service->getTaskDetails($gid);

                // Знаходимо проект
                $project = null;
                if (! empty($taskDetails['memberships'])) {
                    foreach ($taskDetails['memberships'] as $membership) {
                        if (isset($membership['project']['gid'])) {
                            $project = Project::where('asana_id', $membership['project']['gid'])->first();
                            if ($project) {
                                break;
                            }
                        }
                    }
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
                    // Оновлюємо існуючий таск
                    Task::withoutEvents(function () use ($gid, $taskDetails, $project, $section, $userId, $status, $existingTask) {
                        Task::where('gid', $gid)->update([
                            'title' => $taskDetails['name'] ?? '',
                            'description' => $taskDetails['notes'] ?? '',
                            'project_id' => $project?->id ?? $existingTask->project_id, // Используем существующий project_id если новый не найден
                            'section_id' => $section?->id,
                            'user_id' => $userId,
                            'status' => $status,
                            'is_completed' => $taskDetails['completed'] ?? false,
                            'deadline' => $taskDetails['due_on'] ?? null,
                        ]);
                    });

                    Log::info('Task updated from webhook', [
                        'gid' => $gid,
                        'action' => $action,
                        'title' => $taskDetails['name'] ?? '',
                        'project_id' => $project?->id ?? $existingTask->project_id,
                    ]);
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
//                        Task::create([
//                            'gid' => $gid,
//                            'title' => $taskDetails['name'] ?? '',
//                            'description' => $taskDetails['notes'] ?? '',
//                            'project_id' => $project->id,
//                            'section_id' => $section?->id,
//                            'user_id' => $userId,
//                            'status' => $status,
//                            'is_completed' => $taskDetails['completed'] ?? false,
//                            'deadline' => $taskDetails['due_on'] ?? null,
//                        ]);
                    });

                    Log::info('Task created from webhook', [
                        'gid' => $gid,
                        'action' => $action,
                        'title' => $taskDetails['name'] ?? '',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync task from webhook', [
                    'gid' => $gid,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle project events.
     */
    protected function handleProjectEvent(string $action, string $gid, AsanaService $service): void
    {
        if ($action === 'deleted') {
            Project::where('asana_id', $gid)->delete();
            Log::info('Project deleted from webhook', ['gid' => $gid]);

            return;
        }

        if (in_array($action, ['added', 'changed'])) {
            // Можна додати синхронізацію проекту
            Log::info('Project event received', ['action' => $action, 'gid' => $gid]);
        }
    }

    /**
     * Handle story (comment) events.
     */
    protected function handleStoryEvent(string $action, string $gid, array $resource, AsanaService $service): void
    {
        // Story - це коментарі в Asana
        if ($action === 'added') {
            // Знаходимо батьківський таск
            $parentGid = $resource['parent']['gid'] ?? null;
            if (! $parentGid) {
                return;
            }

            $task = Task::where('gid', $parentGid)->first();
            if (! $task) {
                Log::warning('Task not found for story', ['parent_gid' => $parentGid]);

                return;
            }

            // Отримуємо деталі коментаря
            try {
                $storyDetails = $service->getStoryDetails($gid);

                // Перевіряємо, чи це текстовий коментар
                if (($storyDetails['type'] ?? '') === 'comment' && ! empty($storyDetails['text'])) {
                    // Знаходимо автора
                    $authorGid = $storyDetails['created_by']['gid'] ?? null;
                    $author = null;
                    if ($authorGid) {
                        $author = User::where('asana_gid', $authorGid)->first();
                    }

                    // Створюємо коментар (якщо його ще немає)
                    Comment::firstOrCreate(
                        ['asana_gid' => $gid],
                        [
                            'task_id' => $task->id,
                            'user_id' => $author?->id,
                            'content' => $storyDetails['text'],
                        ]
                    );

                    Log::info('Comment synced from webhook', [
                        'story_gid' => $gid,
                        'task_gid' => $parentGid,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync story from webhook', [
                    'gid' => $gid,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle section events.
     */
    protected function handleSectionEvent(string $action, string $gid, AsanaService $service): void
    {
        if ($action === 'deleted') {
            Section::where('asana_gid', $gid)->delete();
            Log::info('Section deleted from webhook', ['gid' => $gid]);

            return;
        }

        if (in_array($action, ['added', 'changed'])) {
            // Можна додати синхронізацію секції
            Log::info('Section event received', ['action' => $action, 'gid' => $gid]);
        }
    }
}
