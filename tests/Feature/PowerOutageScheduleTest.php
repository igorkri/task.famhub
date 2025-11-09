<?php

namespace Tests\Feature;

use App\Jobs\SendPowerOutageNotification;
use App\Models\PowerOutageSchedule;
use App\Services\PowerOutageParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PowerOutageScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_parser_extracts_description_from_html(): void
    {
        $html = file_get_contents(base_path('TODO/result.html'));
        $parser = new PowerOutageParserService;

        $data = $parser->parse($html);

        $this->assertArrayHasKey('description', $data);
        $this->assertStringContainsString('8 листопада 2025 року', $data['description']);
    }

    public function test_parser_extracts_periods_from_html(): void
    {
        $html = file_get_contents(base_path('TODO/result.html'));
        $parser = new PowerOutageParserService;

        $data = $parser->parse($html);

        $this->assertArrayHasKey('periods', $data);
        $this->assertNotEmpty($data['periods']);
        $this->assertEquals('07:00', $data['periods'][0]['from']);
        $this->assertEquals('16:00', $data['periods'][0]['to']);
        $this->assertEquals(2.5, $data['periods'][0]['queues']);
    }

    public function test_parser_extracts_schedule_data_from_html(): void
    {
        $html = file_get_contents(base_path('TODO/result.html'));
        $parser = new PowerOutageParserService;

        $data = $parser->parse($html);

        $this->assertArrayHasKey('schedule_data', $data);
        $this->assertNotEmpty($data['schedule_data']);
        $this->assertArrayHasKey('queue', $data['schedule_data'][0]);
        $this->assertArrayHasKey('subqueue', $data['schedule_data'][0]);
        $this->assertArrayHasKey('hourly_status', $data['schedule_data'][0]);
        $this->assertCount(48, $data['schedule_data'][0]['hourly_status']);
    }

    public function test_command_fetches_and_saves_schedule(): void
    {
        Http::fake([
            'www.poe.pl.ua/*' => Http::response(
                file_get_contents(base_path('TODO/result.html')),
                200
            ),
        ]);

        $this->artisan('power:fetch-schedule', ['date' => '08-11-2025'])
            ->assertExitCode(0);

        $this->assertDatabaseCount('power_outage_schedules', 1);

        $schedule = PowerOutageSchedule::first();
        $this->assertEquals('2025-11-08', $schedule->schedule_date->format('Y-m-d'));
        $this->assertNotNull($schedule->description);
        $this->assertNotEmpty($schedule->periods);
        $this->assertNotEmpty($schedule->schedule_data);
    }

    public function test_hash_generation_is_consistent(): void
    {
        $parser = new PowerOutageParserService;
        $data = ['test' => 'data'];

        $hash1 = $parser->generateHash($data);
        $hash2 = $parser->generateHash($data);

        $this->assertEquals($hash1, $hash2);
    }

    public function test_hash_changes_when_data_changes(): void
    {
        $parser = new PowerOutageParserService;

        $hash1 = $parser->generateHash(['test' => 'data1']);
        $hash2 = $parser->generateHash(['test' => 'data2']);

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_notification_job_formats_message_correctly(): void
    {
        $schedule = PowerOutageSchedule::factory()->create([
            'schedule_date' => '2025-11-08',
            'description' => 'Test description',
            'periods' => [
                ['from' => '07:00', 'to' => '16:00', 'queues' => 2.5],
            ],
            'schedule_data' => [
                [
                    'queue' => '1 черга',
                    'subqueue' => '1',
                    'hourly_status' => array_fill(0, 48, 'on'),
                ],
            ],
        ]);

        Http::fake();

        config(['services.telegram.bot_token' => 'test_token']);
        config(['services.telegram.chat_id' => 'test_chat']);

        $job = new SendPowerOutageNotification($schedule);
        $job->handle();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org') &&
                $request['chat_id'] === 'test_chat' &&
                str_contains($request['text'], '08.11.2025');
        });
    }

    public function test_parser_throws_exception_on_empty_html(): void
    {
        $parser = new PowerOutageParserService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HTML content is empty');

        $parser->parse('');
    }

    public function test_parser_throws_exception_on_whitespace_only_html(): void
    {
        $parser = new PowerOutageParserService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HTML content is empty');

        $parser->parse('   ');
    }

    public function test_command_handles_empty_response(): void
    {
        Http::fake([
            'www.poe.pl.ua/*' => Http::response('', 200),
        ]);

        $this->artisan('power:fetch-schedule', ['date' => '10-11-2025'])
            ->expectsOutput('Получение графика отключений на 10-11-2025...')
            ->assertExitCode(1);
    }
}
