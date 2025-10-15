<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Section;
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

        foreach ($tasks as $asanaTaskData) {
            $sectionGid = $asanaTaskData['memberships'][0]['section']['gid'] ?? null;

            if (! $sectionGid) {
                // Пропускаем задачи без секции
                \Log::warning('Task without section in Asana', [
                    'task_gid' => $asanaTaskData['gid'] ?? null,
                    'task_name' => $asanaTaskData['name'] ?? null,
                ]);

                continue;
            }

            $section = Section::where('project_id', $project->id)
                ->where('asana_gid', $sectionGid)
                ->first();

            if (! $section) {
                // Пропускаем задачи с секциями, которых нет в нашей системе
                \Log::warning('Section not found in local database', [
                    'task_gid' => $asanaTaskData['gid'] ?? null,
                    'task_name' => $asanaTaskData['name'] ?? null,
                    'section_gid' => $sectionGid,
                ]);

                continue;
            }

            // Отключаем observers во время синхронизации из Asana, чтобы избежать циклических обновлений
            Task::withoutEvents(function () use ($asanaTaskData, $project, $section) {
                Task::updateOrCreate(
                    ['gid' => $asanaTaskData['gid'] ?? null],
                    [
                        'project_id' => $project->id,
                        'status' => $section->status,
                        'title' => $asanaTaskData['name'] ?? '',
                        'description' => $asanaTaskData['notes'] ?? '',
                        'is_completed' => $asanaTaskData['completed'] ?? false,
                        'deadline' => $asanaTaskData['due_on'] ?? null,
                    ]
                );
            });
        }
    }
}
