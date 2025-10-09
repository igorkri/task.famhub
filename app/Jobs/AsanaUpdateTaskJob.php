<?php

namespace App\Jobs;

use App\Models\Section;
use App\Models\Task;
use App\Services\AsanaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AsanaUpdateTaskJob implements ShouldQueue
{
    use Queueable;

    protected Task $task;

    /**
     * Create a new job instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        \Log::info('AsanaUpdateTaskJob started', [
            'task_id' => $this->task->id,
            'task_gid' => $this->task->gid,
            'task_status' => $this->task->status,
            'project_id' => $this->task->project_id,
        ]);

        $asanaService = app(AsanaService::class);

        // Находим секцию с соответствующим статусом в том же проекте
        $targetSection = Section::where('project_id', $this->task->project_id)
            ->where('status', $this->task->status)
            ->first();

        if ($targetSection && $targetSection->asana_gid) {
            try {
                // Перемещаем задачу в новую секцию
                $result = $asanaService->moveTaskToSection($this->task->gid, $targetSection->asana_gid);
                \Log::info('Asana task moved to section', ['task_id' => $this->task->id, 'section_gid' => $targetSection->asana_gid, 'result' => $result]);

                // Также обновляем статус завершения, если нужно
                $updateData = [];
                if ($this->task->status === Task::STATUS_COMPLETED) {
                    $updateData['completed'] = true;
                } elseif (in_array($this->task->status, [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS, Task::STATUS_NEEDS_CLARIFICATION])) {
                    $updateData['completed'] = false;
                }

                if (! empty($updateData)) {
                    $updateResult = $asanaService->updateTask($this->task->gid, $updateData);
                    \Log::info('Asana task completion status updated', ['task_id' => $this->task->id, 'update_data' => $updateData]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to update Asana task', ['task_id' => $this->task->id, 'error' => $e->getMessage()]);
            }
        } else {
            \Log::warning('No target section found for task status', [
                'task_id' => $this->task->id,
                'status' => $this->task->status,
                'project_id' => $this->task->project_id,
            ]);
        }
    }
}
