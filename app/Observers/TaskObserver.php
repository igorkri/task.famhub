<?php

namespace App\Observers;

use App\Jobs\AsanaUpdateTaskJob;
use App\Models\Task;
use App\Models\TaskHistory;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        TaskHistory::logEvent(
            task: $task,
            eventType: TaskHistory::EVENT_CREATED,
            source: TaskHistory::SOURCE_LOCAL,
            description: 'Таск створено'
        );
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        // Збираємо всі зміни
        $changes = [];
        $dirtyAttributes = $task->getDirty();

        // Виключаємо технічні поля
        $excludedFields = ['updated_at'];

        foreach ($dirtyAttributes as $field => $newValue) {
            if (in_array($field, $excludedFields)) {
                continue;
            }

            $oldValue = $task->getOriginal($field);
            $changes[$field] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];

            // Логуємо спеціальні події
            if ($field === 'status') {
                TaskHistory::logFieldChange(
                    task: $task,
                    fieldName: $field,
                    oldValue: $oldValue,
                    newValue: $newValue,
                    eventType: TaskHistory::EVENT_STATUS_CHANGED,
                    source: TaskHistory::SOURCE_LOCAL
                );
            } elseif ($field === 'user_id') {
                $eventType = $newValue ? TaskHistory::EVENT_ASSIGNED : TaskHistory::EVENT_UNASSIGNED;
                TaskHistory::logFieldChange(
                    task: $task,
                    fieldName: $field,
                    oldValue: $oldValue,
                    newValue: $newValue,
                    eventType: $eventType,
                    source: TaskHistory::SOURCE_LOCAL
                );
            } elseif ($field === 'section_id') {
                TaskHistory::logFieldChange(
                    task: $task,
                    fieldName: $field,
                    oldValue: $oldValue,
                    newValue: $newValue,
                    eventType: TaskHistory::EVENT_SECTION_CHANGED,
                    source: TaskHistory::SOURCE_LOCAL
                );
            } elseif ($field === 'priority') {
                TaskHistory::logFieldChange(
                    task: $task,
                    fieldName: $field,
                    oldValue: $oldValue,
                    newValue: $newValue,
                    eventType: TaskHistory::EVENT_PRIORITY_CHANGED,
                    source: TaskHistory::SOURCE_LOCAL
                );
            } elseif ($field === 'deadline') {
                TaskHistory::logFieldChange(
                    task: $task,
                    fieldName: $field,
                    oldValue: $oldValue,
                    newValue: $newValue,
                    eventType: TaskHistory::EVENT_DEADLINE_CHANGED,
                    source: TaskHistory::SOURCE_LOCAL
                );
            } elseif ($field === 'is_completed') {
                $eventType = $newValue ? TaskHistory::EVENT_COMPLETED : TaskHistory::EVENT_REOPENED;
                TaskHistory::logFieldChange(
                    task: $task,
                    fieldName: $field,
                    oldValue: $oldValue,
                    newValue: $newValue,
                    eventType: $eventType,
                    source: TaskHistory::SOURCE_LOCAL
                );
            } else {
                // Для інших полів просто логуємо зміну
                TaskHistory::logFieldChange(
                    task: $task,
                    fieldName: $field,
                    oldValue: $oldValue,
                    newValue: $newValue,
                    eventType: TaskHistory::EVENT_UPDATED,
                    source: TaskHistory::SOURCE_LOCAL
                );
            }
        }

        // Проверяем, изменился ли статус (для синхронізації з Asana)
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
        TaskHistory::logEvent(
            task: $task,
            eventType: TaskHistory::EVENT_DELETED,
            source: TaskHistory::SOURCE_LOCAL,
            description: 'Таск видалено'
        );
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        TaskHistory::logEvent(
            task: $task,
            eventType: TaskHistory::EVENT_CREATED,
            source: TaskHistory::SOURCE_LOCAL,
            description: 'Таск відновлено'
        );
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }
}
