<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Task;
use App\Services\AsanaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProjectAsanaTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Project $project)
    {
        // Конструктор с promoted property
    }

    public function handle(AsanaService $service): void
    {
        $project = $this->project->fresh();

        $asanaProjectId = $project->asana_id ?? null;
        if (! $asanaProjectId) {
            // Nothing to do
            return;
        }

        $tasks = $service->getProjectTasks($asanaProjectId);

        foreach ($tasks as $t) {
            Task::updateOrCreate(
                ['gid' => $t['gid'] ?? null],
                [
                    'project_id' => $project->id,
                    'title' => $t['name'] ?? '',
                    'description' => $t['notes'] ?? '',
                    'is_completed' => $t['completed'] ?? false,
                    'deadline' => $t['due_on'] ?? null,
                ]
            );
        }
    }
}
