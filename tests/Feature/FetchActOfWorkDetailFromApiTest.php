<?php

namespace Tests\Feature;

use App\Models\ActOfWork;
use App\Models\ActOfWorkDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchActOfWorkDetailFromApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fetches_act_of_work_detail_successfully(): void
    {
        Http::fake([
            '*' => Http::response([
                ['id' => 1, 'task_name' => 'Task 1', 'hours' => 5, 'amount' => 2000],
                ['id' => 2, 'task_name' => 'Task 2', 'hours' => 3, 'amount' => 1200],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-detail-from-api', ['--act-id' => '23'])
            ->expectsOutput('Data fetched successfully. Total records: 2')
            ->assertExitCode(0);
    }

    public function test_command_requires_act_id(): void
    {
        $this->artisan('app:fetch-act-of-work-detail-from-api')
            ->expectsOutput('Act ID is required. Use --act-id option.')
            ->assertExitCode(1);
    }

    public function test_command_handles_api_failure(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this->artisan('app:fetch-act-of-work-detail-from-api', ['--act-id' => '23'])
            ->expectsOutput('Failed to fetch data. Status code: 500')
            ->assertExitCode(1);
    }

    public function test_command_handles_empty_response(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-detail-from-api', ['--act-id' => '23'])
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

        $this->artisan('app:fetch-act-of-work-detail-from-api', [
            '--act-id' => '23',
            '--url' => 'https://custom-api.example.com/data',
        ])
            ->expectsOutputToContain('Fetching act of work detail from: https://custom-api.example.com/data')
            ->assertExitCode(0);
    }

    public function test_command_saves_data_to_file_with_act_id(): void
    {
        Http::fake([
            '*' => Http::response([
                ['id' => 1, 'task_name' => 'Task 1'],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-detail-from-api', [
            '--act-id' => '23',
            '--save' => true,
        ])
            ->expectsOutputToContain('Data saved to:')
            ->assertExitCode(0);
    }

    public function test_command_displays_data_as_table(): void
    {
        Http::fake([
            '*' => Http::response([
                ['id' => 1, 'task_name' => 'Task 1'],
                ['id' => 2, 'task_name' => 'Task 2'],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-detail-from-api', [
            '--act-id' => '23',
            '--format' => 'table',
        ])
            ->assertExitCode(0);
    }

    public function test_command_imports_data_to_database(): void
    {
        $user = User::factory()->create();
        $actOfWork = ActOfWork::factory()->create([
            'number' => 'ACT-001',
            'user_id' => $user->id,
        ]);

        Http::fake([
            '*' => Http::response([
                [
                    'task_gid' => '1234567890',
                    'project_gid' => '9876543210',
                    'project' => 'Test Project',
                    'task' => 'Test Task',
                    'description' => 'Test Description',
                    'amount' => 2000,
                    'hours' => 5,
                ],
                [
                    'task_gid' => '1234567891',
                    'project_gid' => '9876543210',
                    'project' => 'Test Project',
                    'task' => 'Another Task',
                    'description' => 'Another Description',
                    'amount' => 1200,
                    'hours' => 3,
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-detail-from-api', [
            '--act-id' => $actOfWork->number,
            '--import' => true,
            '--no-interaction' => true,
        ])
            ->expectsOutput('Starting import to database...')
            ->expectsOutput('Import completed:')
            ->assertExitCode(0);

        $this->assertDatabaseHas('act_of_work_details', [
            'act_of_work_id' => $actOfWork->id,
            'task_gid' => '1234567890',
            'amount' => 2000,
        ]);

        $this->assertDatabaseHas('act_of_work_details', [
            'act_of_work_id' => $actOfWork->id,
            'task_gid' => '1234567891',
            'amount' => 1200,
        ]);

        $this->assertEquals(2, ActOfWorkDetail::count());
    }

    public function test_command_fails_import_without_parent_act(): void
    {
        Http::fake([
            '*' => Http::response([
                ['task_gid' => '1234567890', 'amount' => 2000],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-detail-from-api', [
            '--act-id' => '999',
            '--import' => true,
            '--no-interaction' => true,
        ])
            ->expectsOutput('Act of work not found with ID/number: 999')
            ->assertExitCode(0);

        $this->assertEquals(0, ActOfWorkDetail::count());
    }

    public function test_command_skips_records_without_required_fields(): void
    {
        $user = User::factory()->create();
        $actOfWork = ActOfWork::factory()->create([
            'number' => 'ACT-001',
            'user_id' => $user->id,
        ]);

        Http::fake([
            '*' => Http::response([
                [
                    'task_gid' => '1234567890',
                    'project_gid' => '9876543210',
                    'amount' => 2000,
                ],
                [
                    // Missing both task_gid and project_gid - should be skipped
                    'amount' => 1000,
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-detail-from-api', [
            '--act-id' => $actOfWork->number,
            '--import' => true,
            '--no-interaction' => true,
        ])
            ->assertExitCode(0);

        $this->assertEquals(1, ActOfWorkDetail::count());
    }

    public function test_command_truncates_table_before_import(): void
    {
        $user = User::factory()->create();
        $actOfWork = ActOfWork::factory()->create([
            'number' => 'ACT-001',
            'user_id' => $user->id,
        ]);

        // Create existing records
        ActOfWorkDetail::factory()->count(3)->create([
            'act_of_work_id' => $actOfWork->id,
        ]);
        $this->assertEquals(3, ActOfWorkDetail::count());

        Http::fake([
            '*' => Http::response([
                [
                    'task_gid' => 'NEW-TASK',
                    'project_gid' => 'NEW-PROJECT',
                    'amount' => 5000,
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-detail-from-api', [
            '--act-id' => $actOfWork->number,
            '--import' => true,
            '--truncate' => true,
            '--no-interaction' => true,
        ])
            ->expectsOutput('ActOfWorkDetails table truncated.')
            ->assertExitCode(0);

        $this->assertEquals(1, ActOfWorkDetail::count());
        $this->assertDatabaseHas('act_of_work_details', [
            'task_gid' => 'NEW-TASK',
        ]);
    }

    public function test_command_updates_existing_records(): void
    {
        $user = User::factory()->create();
        $actOfWork = ActOfWork::factory()->create([
            'number' => 'ACT-001',
            'user_id' => $user->id,
        ]);

        ActOfWorkDetail::factory()->create([
            'act_of_work_id' => $actOfWork->id,
            'task_gid' => 'TASK-123',
            'project_gid' => 'PROJECT-456',
            'amount' => 1000,
        ]);

        Http::fake([
            '*' => Http::response([
                [
                    'task_gid' => 'TASK-123',
                    'project_gid' => 'PROJECT-456',
                    'amount' => 5000,
                    'hours' => 10,
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-detail-from-api', [
            '--act-id' => $actOfWork->number,
            '--import' => true,
            '--no-interaction' => true,
        ])
            ->assertExitCode(0);

        $this->assertEquals(1, ActOfWorkDetail::count());
        $this->assertDatabaseHas('act_of_work_details', [
            'task_gid' => 'TASK-123',
            'amount' => 5000,
            'hours' => 10,
        ]);
    }
}
