<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Section;
use App\Services\AsanaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AsanaSyncSectionsJob implements ShouldQueue
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
            // Получаем секции из каждого проекта в Asana
            $asanaSections = $asanaService->getProjectSections($project->asana_id);

            foreach ($asanaSections as $asanaSection) {
                Section::updateOrCreate(
                    ['asana_gid' => $asanaSection->gid],
                    [
                        'project_id' => $project->id,
                        'name' => $asanaSection->name ?? '',
                        'status' => null, // По умолчанию null, можно связать позже
                    ]
                );
            }
        }
    }
}
