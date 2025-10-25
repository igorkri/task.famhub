<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\Time;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchTimerDataFromApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fetches_data_successfully(): void
    {
        Http::fake([
            '*' => Http::response([
                ['id' => 1, 'task' => 'Test Task', 'duration' => 3600],
                ['id' => 2, 'task' => 'Another Task', 'duration' => 7200],
            ], 200),
        ]);

        $this->artisan('app:fetch-timer-data-from-api')
            ->expectsOutput('Data fetched successfully. Total records: 2')
            ->assertExitCode(0);
    }

    public function test_command_handles_api_failure(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this->artisan('app:fetch-timer-data-from-api')
            ->expectsOutput('Failed to fetch data. Status code: 500')
            ->assertExitCode(1);
    }

    public function test_command_handles_empty_response(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $this->artisan('app:fetch-timer-data-from-api')
            ->expectsOutput('No data received from API')
            ->assertExitCode(0);
    }

    public function test_command_accepts_custom_url(): void
    {
        Http::fake([
            'https://custom-api.example.com/*' => Http::response([
                ['id' => 1, 'data' => 'test'],
            ], 200),
        ]);

        $this->artisan('app:fetch-timer-data-from-api', ['--url' => 'https://custom-api.example.com/data'])
            ->expectsOutputToContain('Fetching data from: https://custom-api.example.com/data')
            ->assertExitCode(0);
    }

    public function test_command_saves_data_to_file(): void
    {
        Http::fake([
            '*' => Http::response([
                ['id' => 1, 'task' => 'Test Task'],
            ], 200),
        ]);

        $this->artisan('app:fetch-timer-data-from-api', ['--save' => true])
            ->expectsOutputToContain('Data saved to:')
            ->assertExitCode(0);
    }

    public function test_command_imports_data_to_database(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'gid' => '1234567890',
            'project_id' => $project->id,
            'user_id' => $user->id,
            'title' => 'Test Task',
        ]);

        Http::fake([
            '*' => Http::response([
                [
                    'task_gid' => '1234567890',
                    'minutes' => 120,
                    'coefficient' => 1.2,
                    'status' => 0,
                    'status_act' => 'ok',
                    'comment' => 'Test comment',
                    'archive' => false,
                    'created_at' => '2025-01-15 10:00:00',
                    'updated_at' => '2025-01-15 11:00:00',
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-timer-data-from-api', ['--import' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Data fetched successfully')
            ->expectsOutputToContain('Import completed')
            ->assertExitCode(0);

        $this->assertDatabaseHas('times', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'duration' => 7200, // 120 minutes * 60 seconds
            'coefficient' => 1.2,
            'status' => Time::STATUS_COMPLETED,
        ]);
    }

    public function test_command_truncates_table_before_import(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'gid' => '1234567890',
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        // Create existing time record
        Time::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('times', 1);

        Http::fake([
            '*' => Http::response([
                [
                    'task_gid' => '1234567890',
                    'minutes' => 60,
                    'coefficient' => 1.0,
                    'status' => 1,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-timer-data-from-api', ['--import' => true, '--truncate' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Times table truncated')
            ->expectsOutputToContain('Import completed')
            ->assertExitCode(0);

        // After truncate and import, should have exactly 1 new record
        $this->assertDatabaseHas('times', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'duration' => 3600, // 60 minutes in seconds
        ]);
    }

    public function test_command_skips_records_without_task(): void
    {
        Http::fake([
            '*' => Http::response([
                [
                    'task_gid' => 'nonexistent',
                    'minutes' => 60,
                    'coefficient' => 1.0,
                    'status' => 0,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-timer-data-from-api', ['--import' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Skipped')
            ->assertExitCode(0);

        $this->assertDatabaseCount('times', 0);
    }
}
