<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\AsanaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTaskFromAsana implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Task $task) {}

    public function handle(AsanaService $service): void
    {
        $task = $this->task->fresh();

        $asanaTaskId = $task->gid ?? null;
        if (! $asanaTaskId) {
            return;
        }

        $details = $service->getTaskDetails($asanaTaskId);

        if (! $details) {
            return;
        }

        // Находим проект
        $project = null;
        $project_id = $task->project_id; // Сохраняем текущий project_id по умолчанию

        if (! empty($details['memberships'])) {
            foreach ($details['memberships'] as $membership) {
                if (isset($membership['project']['gid'])) {
                    $newProject = \App\Models\Project::where('asana_id', $membership['project']['gid'])->first();
                    if ($newProject) {
                        $project_id = $newProject->id;
                        break;
                    }
                }
            }
        }

        $task->update([
            'title' => $details['name'] ?? $task->title,
            'description' => $details['notes'] ?? $task->description,
            'is_completed' => $details['completed'] ?? $task->is_completed,
            'deadline' => $details['due_on'] ?? $task->deadline,
            'project_id' => $project_id, // Обновляем project_id, сохраняя существующий если новый не найден
        ]);

        \Log::info('Task synced from Asana', [
            'task_id' => $task->id,
            'asana_gid' => $asanaTaskId,
            'project_id' => $project_id
        ]);
    }
}
