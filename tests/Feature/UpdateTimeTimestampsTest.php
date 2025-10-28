<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\Time;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateTimeTimestampsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_fails_when_time_not_found(): void
    {
        $exitCode = Artisan::call('masterok:update-time-timestamps', [
            '--time-id' => 999999,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    public function test_command_handles_api_failure(): void
    {
        // Мокаем HTTP запрос с ошибкой
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $exitCode = Artisan::call('masterok:update-time-timestamps', [
            '--limit' => 1,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    public function test_command_handles_empty_api_response(): void
    {
        // Мокаем HTTP запрос с пустым ответом
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $exitCode = Artisan::call('masterok:update-time-timestamps', [
            '--limit' => 1,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_updates_time_timestamps(): void
    {
        // Создаем задачу с gid
        $task = Task::factory()->create([
            'gid' => '1211692396550896',
        ]);

        // Создаем запись времени с одинаковыми timestamps
        $time = Time::factory()->create([
            'task_id' => $task->id,
            'user_id' => $task->user_id ?? 1,
            'duration' => 2261, // 00:37:41 в секундах
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        // Мокаем API ответ
        Http::fake([
            '*' => Http::response([
                [
                    'id' => 749,
                    'task_gid' => '1211692396550896',
                    'time' => '00:37:41',
                    'minute' => 37,
                    'coefficient' => 1,
                    'comment' => null,
                    'status' => 1,
                    'archive' => 0,
                    'status_act' => 'not_ok',
                    'created_at' => '2025-10-23 12:13:51',
                    'updated_at' => '2025-10-23 14:43:28',
                ],
            ], 200),
        ]);

        // Запускаем команду
        $exitCode = Artisan::call('masterok:update-time-timestamps', [
            '--time-id' => $time->id,
        ]);

        $this->assertEquals(0, $exitCode);

        // Проверяем, что timestamps обновились
        $time->refresh();

        $this->assertEquals('2025-10-23 12:13:51', $time->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-23 14:43:28', $time->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_command_skips_times_without_task_gid(): void
    {
        // Создаем задачу БЕЗ gid
        $task = Task::factory()->create([
            'gid' => null,
        ]);

        // Создаем запись времени
        $time = Time::factory()->create([
            'task_id' => $task->id,
            'user_id' => $task->user_id ?? 1,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        // Мокаем API ответ
        Http::fake([
            '*' => Http::response([
                [
                    'task_gid' => 'different_gid',
                    'time' => '00:37:41',
                    'created_at' => '2025-10-23 12:13:51',
                    'updated_at' => '2025-10-23 14:43:28',
                ],
            ], 200),
        ]);

        $originalCreatedAt = $time->created_at;
        $originalUpdatedAt = $time->updated_at;

        // Запускаем команду
        $exitCode = Artisan::call('masterok:update-time-timestamps', [
            '--time-id' => $time->id,
        ]);

        $this->assertEquals(0, $exitCode);

        // Проверяем, что timestamps НЕ изменились
        $time->refresh();

        $this->assertEquals($originalCreatedAt->format('Y-m-d H:i:s'), $time->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals($originalUpdatedAt->format('Y-m-d H:i:s'), $time->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_command_processes_multiple_times_with_limit(): void
    {
        // Создаем задачу
        $task = Task::factory()->create([
            'gid' => '1211692396550896',
        ]);

        // Создаем несколько записей времени с одинаковыми timestamps
        $times = Time::factory()->count(5)->create([
            'task_id' => $task->id,
            'user_id' => $task->user_id ?? 1,
            'duration' => 2261,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        // Мокаем API ответ
        Http::fake([
            '*' => Http::response([
                [
                    'task_gid' => '1211692396550896',
                    'time' => '00:37:41',
                    'created_at' => '2025-10-23 12:13:51',
                    'updated_at' => '2025-10-23 14:43:28',
                ],
            ], 200),
        ]);

        // Запускаем команду с лимитом 3
        $exitCode = Artisan::call('masterok:update-time-timestamps', [
            '--limit' => 3,
        ]);

        $this->assertEquals(0, $exitCode);

        // Проверяем, что обработаны записи
        $updatedCount = Time::whereRaw('created_at != updated_at')->count();

        $this->assertGreaterThanOrEqual(3, $updatedCount);
    }

    public function test_command_matches_by_duration(): void
    {
        // Создаем задачу
        $task = Task::factory()->create([
            'gid' => '1211692396550896',
        ]);

        // Создаем два записи с разными duration
        $time1 = Time::factory()->create([
            'task_id' => $task->id,
            'user_id' => $task->user_id ?? 1,
            'duration' => 2261, // 00:37:41
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        $time2 = Time::factory()->create([
            'task_id' => $task->id,
            'user_id' => $task->user_id ?? 1,
            'duration' => 3661, // 01:01:01
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        // Мокаем API ответ с двумя записями
        Http::fake([
            '*' => Http::response([
                [
                    'task_gid' => '1211692396550896',
                    'time' => '00:37:41',
                    'created_at' => '2025-10-23 12:13:51',
                    'updated_at' => '2025-10-23 14:43:28',
                ],
                [
                    'task_gid' => '1211692396550896',
                    'time' => '01:01:01',
                    'created_at' => '2025-10-24 09:00:00',
                    'updated_at' => '2025-10-24 11:00:00',
                ],
            ], 200),
        ]);

        // Запускаем команду
        $exitCode = Artisan::call('masterok:update-time-timestamps', [
            '--limit' => 10,
        ]);

        $this->assertEquals(0, $exitCode);

        // Проверяем, что каждая запись получила правильные timestamps
        $time1->refresh();
        $time2->refresh();

        $this->assertEquals('2025-10-23 12:13:51', $time1->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-24 09:00:00', $time2->created_at->format('Y-m-d H:i:s'));
    }
}

