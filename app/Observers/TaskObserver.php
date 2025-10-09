<?php

namespace App\Observers;

use App\Jobs\AsanaUpdateTaskJob;
use App\Models\Task;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        // Проверяем, изменился ли статус
        if ($task->wasChanged('status') && $task->gid) {
            \Log::info('Task status changed, dispatching AsanaUpdateTaskJob', [
                'task_id' => $task->id,
                'old_status' => $task->getOriginal('status'),
                'new_status' => $task->status,
            ]);
            // Запускаем job для обновления Asana
            AsanaUpdateTaskJob::dispatch($task);
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }
}
