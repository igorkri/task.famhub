<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Section;
use App\Models\Task;
use App\Models\User;
use App\Services\AsanaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
                // Обработка assignee
                $userId = null;
                if (isset($asanaTask->assignee) && $asanaTask->assignee) {
                    $userData = [
                        'name' => $asanaTask->assignee->name ?? '',
                    ];
                    if (isset($asanaTask->assignee->email) && $asanaTask->assignee->email) {
                        $userData['email'] = $asanaTask->assignee->email;
                    }
                    $user = User::updateOrCreate(
                        ['asana_gid' => $asanaTask->assignee->gid],
                        $userData
                    );
                    $userId = $user->id;
                }

                // Обработка section
                $sectionId = null;
                if (isset($asanaTask->memberships) && is_array($asanaTask->memberships)) {
                    foreach ($asanaTask->memberships as $membership) {
                        if (isset($membership->section) && $membership->section) {
                            $section = Section::where('asana_gid', $membership->section->gid)->first();
                            if ($section) {
                                $sectionId = $section->id;
                                break;
                            }
                        }
                    }
                }

                // Определение статуса: если секция имеет статус, используем его, иначе 'new'
                $status = 'new';
                if ($sectionId) {
                    $section = Section::find($sectionId);
                    if ($section && $section->status) {
                        $status = $section->status;
                    }
                }

                Task::updateOrCreate(
                    ['gid' => $asanaTask->gid],
                    [
                        'title' => $asanaTask->name ?? '',
                        'project_id' => $project->id,
                        'user_id' => $userId,
                        'section_id' => $sectionId,
                        'description' => $asanaTask->notes ?? '',
                        'status' => $status,
                        'is_completed' => $asanaTask->completed ?? false,
                        'deadline' => isset($asanaTask->due_on) ? $asanaTask->due_on : null,
                    ]
                );
            }
        }
    }
}
