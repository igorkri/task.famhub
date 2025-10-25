<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchActOfWorkDetailFromApiTest extends TestCase
{
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
}
