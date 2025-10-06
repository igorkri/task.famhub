<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\AsanaService;
use App\Models\Task;
use App\Models\Project;

class AsanaSyncTasksJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $asanaService = app(AsanaService::class);

        // Получаем все проекты из базы, которые имеют asana_id
        $projects = Project::whereNotNull('asana_id')->get();

        foreach ($projects as $project) {
            // Получаем задачи из каждого проекта в Asana
            $asanaTasks = $asanaService->getProjectTasks($project->asana_id);

            foreach ($asanaTasks as $asanaTask) {
                Task::updateOrCreate(
                    ['gid' => $asanaTask->gid],
                    [
                        'title' => $asanaTask->name ?? '',
                        'project_id' => $project->id,
                        'description' => '', // Базовая синхронизация
                        'status' => 'new', // По умолчанию новые задачи
                        'is_completed' => false, // По умолчанию не завершены
                    ]
                );
            }
        }
    }
}
