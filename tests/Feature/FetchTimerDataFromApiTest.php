<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchTimerDataFromApiTest extends TestCase
{
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
}
