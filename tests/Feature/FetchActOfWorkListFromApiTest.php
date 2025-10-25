<?php

namespace Tests\Feature;

use App\Models\ActOfWork;
use App\Models\ActOfWorkDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchActOfWorkListFromApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fetches_act_of_work_list_successfully(): void
    {
        Http::fake([
            '*' => Http::response([
                ['id' => 1, 'number' => 'ACT-001', 'date' => '2025-01-15', 'total' => 5000],
                ['id' => 2, 'number' => 'ACT-002', 'date' => '2025-01-20', 'total' => 7500],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-list-from-api')
            ->expectsOutput('Data fetched successfully. Total records: 2')
            ->assertExitCode(0);
    }

    public function test_command_handles_api_failure(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this->artisan('app:fetch-act-of-work-list-from-api')
            ->expectsOutput('Failed to fetch data. Status code: 500')
            ->assertExitCode(1);
    }

    public function test_command_handles_empty_response(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-list-from-api')
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

        $this->artisan('app:fetch-act-of-work-list-from-api', ['--url' => 'https://custom-api.example.com/data'])
            ->expectsOutputToContain('Fetching act of work list from: https://custom-api.example.com/data')
            ->assertExitCode(0);
    }

    public function test_command_saves_data_to_file(): void
    {
        Http::fake([
            '*' => Http::response([
                ['id' => 1, 'number' => 'ACT-001'],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-list-from-api', ['--save' => true])
            ->expectsOutputToContain('Data saved to:')
            ->assertExitCode(0);
    }

    public function test_command_displays_data_as_table(): void
    {
        Http::fake([
            '*' => Http::response([
                ['id' => 1, 'number' => 'ACT-001'],
                ['id' => 2, 'number' => 'ACT-002'],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-list-from-api', ['--format' => 'table'])
            ->assertExitCode(0);
    }

    public function test_command_imports_data_to_database(): void
    {
        $user = User::factory()->create();

        Http::fake([
            '*' => Http::response([
                [
                    'number' => 'ACT-001',
                    'status' => 'pending',
                    'user_id' => $user->id,
                    'date' => '2025-01-15',
                    'total_amount' => 5000,
                    'paid_amount' => 0,
                ],
                [
                    'number' => 'ACT-002',
                    'status' => 'paid',
                    'user_id' => $user->id,
                    'date' => '2025-01-20',
                    'total_amount' => 7500,
                    'paid_amount' => 7500,
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-list-from-api', ['--import' => true, '--no-interaction' => true])
            ->expectsOutput('Starting import to database...')
            ->expectsOutput('Import completed:')
            ->assertExitCode(0);

        $this->assertDatabaseHas('act_of_works', [
            'number' => 'ACT-001',
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('act_of_works', [
            'number' => 'ACT-002',
            'status' => 'paid',
            'user_id' => $user->id,
        ]);

        $this->assertEquals(2, ActOfWork::count());
    }

    public function test_command_skips_records_without_required_fields(): void
    {
        $user = User::factory()->create();

        Http::fake([
            '*' => Http::response([
                [
                    'number' => 'ACT-001',
                    'user_id' => $user->id,
                ],
                [
                    // Missing number - should be skipped
                    'user_id' => $user->id,
                    'status' => 'pending',
                ],
                [
                    // Missing user - should be skipped
                    'number' => 'ACT-003',
                    'user_id' => 999999,
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-list-from-api', ['--import' => true, '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertEquals(1, ActOfWork::count());
        $this->assertDatabaseHas('act_of_works', [
            'number' => 'ACT-001',
        ]);
    }

    public function test_command_truncates_table_before_import(): void
    {
        $user = User::factory()->create();

        // Create existing records
        ActOfWork::factory()->count(3)->create(['user_id' => $user->id]);
        $this->assertEquals(3, ActOfWork::count());

        Http::fake([
            '*' => Http::response([
                [
                    'number' => 'ACT-NEW',
                    'user_id' => $user->id,
                    'status' => 'pending',
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-list-from-api', [
            '--import' => true,
            '--truncate' => true,
            '--no-interaction' => true,
        ])
            ->expectsOutput('ActOfWorks table truncated.')
            ->assertExitCode(0);

        $this->assertEquals(1, ActOfWork::count());
        $this->assertDatabaseHas('act_of_works', [
            'number' => 'ACT-NEW',
        ]);
    }

    public function test_command_updates_existing_records(): void
    {
        $user = User::factory()->create();

        ActOfWork::factory()->create([
            'number' => 'ACT-001',
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 1000,
        ]);

        Http::fake([
            '*' => Http::response([
                [
                    'number' => 'ACT-001',
                    'user_id' => $user->id,
                    'status' => 'paid',
                    'total_amount' => 5000,
                    'paid_amount' => 5000,
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-list-from-api', ['--import' => true, '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertEquals(1, ActOfWork::count());
        $this->assertDatabaseHas('act_of_works', [
            'number' => 'ACT-001',
            'status' => 'paid',
            'total_amount' => 5000,
        ]);
    }

    public function test_command_imports_acts_with_details(): void
    {
        $user = User::factory()->create();

        // Mock act list API
        Http::fake([
            '*/act-of-work/list' => Http::response([
                [
                    'number' => 'ACT-001',
                    'user_id' => $user->id,
                    'status' => 'paid',
                    'total_amount' => 5000,
                    'paid_amount' => 5000,
                ],
            ], 200),
            '*/act-of-work-detail/by-act*' => Http::response([
                [
                    'task_gid' => '1234567890',
                    'project_gid' => '9876543210',
                    'project' => 'Test Project',
                    'task' => 'Test Task',
                    'description' => 'Test Description',
                    'amount' => 2500,
                    'hours' => 5,
                ],
                [
                    'task_gid' => '1234567891',
                    'project_gid' => '9876543210',
                    'project' => 'Test Project',
                    'task' => 'Another Task',
                    'amount' => 2500,
                    'hours' => 5,
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-act-of-work-list-from-api', [
            '--import' => true,
            '--with-details' => true,
            '--no-interaction' => true,
        ])
            ->expectsOutput('Starting import to database...')
            ->expectsOutput('Import completed:')
            ->assertExitCode(0);

        // Check act was imported
        $this->assertEquals(1, ActOfWork::count());
        $this->assertDatabaseHas('act_of_works', [
            'number' => 'ACT-001',
            'status' => 'paid',
        ]);

        // Check details were imported
        $actOfWork = ActOfWork::where('number', 'ACT-001')->first();
        $this->assertEquals(2, ActOfWorkDetail::where('act_of_work_id', $actOfWork->id)->count());

        $this->assertDatabaseHas('act_of_work_details', [
            'act_of_work_id' => $actOfWork->id,
            'task_gid' => '1234567890',
            'amount' => 2500,
        ]);

        $this->assertDatabaseHas('act_of_work_details', [
            'act_of_work_id' => $actOfWork->id,
            'task_gid' => '1234567891',
            'amount' => 2500,
        ]);
    }
}
