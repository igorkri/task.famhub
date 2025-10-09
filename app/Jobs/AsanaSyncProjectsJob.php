<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\AsanaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AsanaSyncProjectsJob implements ShouldQueue
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
        $workspaceId = config('services.asana.workspace_id');
        $asanaService = app(AsanaService::class);
        $projects = $asanaService->getWorkspaceProjects($workspaceId);

        // Найдем или создадим workspace для Asana
        $workspace = \App\Models\Workspace::firstOrCreate(
            ['gid' => $workspaceId],
            [
                'name' => 'Asana Workspace',
                'description' => 'Рабочее пространство из Asana',
            ]
        );

        foreach ($projects as $asanaProject) {
            Project::updateOrCreate(
                ['asana_id' => $asanaProject->gid],
                [
                    'name' => $asanaProject->name ?? '',
                    'description' => '',
                    'workspace_id' => $workspace->id, // Используем ID из базы, а не Asana ID
                ]
            );
        }
    }
}
