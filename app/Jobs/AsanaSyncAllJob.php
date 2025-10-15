<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AsanaSyncAllJob implements ShouldQueue
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
        // Запускаем синхронизацию проектов, затем секций, затем задач синхронно
        (new AsanaSyncProjectsJob)->handle();
        (new AsanaSyncSectionsJob)->handle();
        (new AsanaSyncTasksJob)->handle();
    }
}
