<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\AsanaSyncAllJob;

class AsanaSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asana:sync {--queue : Run in queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all data from Asana (projects, sections, tasks, users)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('queue')) {
            AsanaSyncAllJob::dispatch();
            $this->info('Asana sync job dispatched to queue.');
        } else {
            // Run synchronously
            $job = new AsanaSyncAllJob();
            $job->handle();
            $this->info('Asana sync completed synchronously.');
        }
    }
}
