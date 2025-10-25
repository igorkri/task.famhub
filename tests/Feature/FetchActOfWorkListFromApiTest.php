<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchActOfWorkListFromApiTest extends TestCase
{
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
}
