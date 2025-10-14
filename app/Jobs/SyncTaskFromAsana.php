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

        $task->update([
            'title' => $details['name'] ?? $task->title,
            'description' => $details['notes'] ?? $task->description,
            'is_completed' => $details['completed'] ?? $task->is_completed,
            'deadline' => $details['due_on'] ?? $task->deadline,
        ]);
    }
}
