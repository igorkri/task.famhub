<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\AsanaService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncTaskCommentsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Task $task
    ) {}

    public function handle(): void
    {
        if (! $this->task->gid) {
            return;
        }

        $service = app(AsanaService::class);

        try {
            $asanaComments = $service->getTaskComments($this->task->gid);

            foreach ($asanaComments as $asanaComment) {
                // Проверяем, существует ли уже комментарий с таким gid
                $existingComment = TaskComment::where('asana_gid', $asanaComment['gid'])->first();

                if (! $existingComment) {
                    // Находим пользователя по email из Asana
                    $user = null;
                    if (isset($asanaComment['created_by']['email'])) {
                        $user = User::where('email', $asanaComment['created_by']['email'])->first();
                    }

                    // Создаем новый комментарий
                    TaskComment::create([
                        'task_id' => $this->task->id,
                        'user_id' => $user ? $user->id : 1, // используем первого пользователя, если не найден
                        'asana_gid' => $asanaComment['gid'],
                        'content' => $asanaComment['text'],
                        'asana_created_at' => isset($asanaComment['created_at']) ? Carbon::parse($asanaComment['created_at']) : now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to sync task comments', [
                'task_id' => $this->task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
