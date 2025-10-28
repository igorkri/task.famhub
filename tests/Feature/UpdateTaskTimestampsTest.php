<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Services\AsanaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class UpdateTaskTimestampsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_fails_when_task_not_found(): void
    {
        $exitCode = Artisan::call('asana:update-timestamps', [
            '--task-id' => 999999,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    public function test_command_fails_when_task_has_no_gid(): void
    {
        $task = Task::factory()->create([
            'gid' => null,
        ]);

        $exitCode = Artisan::call('asana:update-timestamps', [
            '--task-id' => $task->id,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    public function test_command_updates_task_timestamps(): void
    {
        // Создаем задачу с одинаковыми timestamps
        $task = Task::factory()->create([
            'gid' => '1234567890',
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        // Мокаем AsanaService
        $mockService = Mockery::mock(AsanaService::class);
        $mockService->shouldReceive('getTaskDetails')
            ->once()
            ->with($task->gid)
            ->andReturn([
                'gid' => $task->gid,
                'name' => $task->title,
                'created_at' => '2024-01-15T08:30:00.000Z',
                'modified_at' => '2024-02-20T14:45:00.000Z',
            ]);

        $this->app->instance(AsanaService::class, $mockService);

        // Запускаем команду
        $exitCode = Artisan::call('asana:update-timestamps', [
            '--task-id' => $task->id,
        ]);

        $this->assertEquals(0, $exitCode);

        // Проверяем, что timestamps обновились
        $task->refresh();

        $this->assertEquals('2024-01-15 08:30:00', $task->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-02-20 14:45:00', $task->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_command_handles_tasks_without_timestamps_in_asana(): void
    {
        $task = Task::factory()->create([
            'gid' => '1234567891',
        ]);

        // Мокаем AsanaService - возвращаем пустые timestamps
        $mockService = Mockery::mock(AsanaService::class);
        $mockService->shouldReceive('getTaskDetails')
            ->once()
            ->with($task->gid)
            ->andReturn([
                'gid' => $task->gid,
                'name' => $task->title,
                'created_at' => null,
                'modified_at' => null,
            ]);

        $this->app->instance(AsanaService::class, $mockService);

        $originalCreatedAt = $task->created_at;
        $originalUpdatedAt = $task->updated_at;

        // Запускаем команду
        $exitCode = Artisan::call('asana:update-timestamps', [
            '--task-id' => $task->id,
        ]);

        $this->assertEquals(0, $exitCode);

        // Проверяем, что timestamps не изменились
        $task->refresh();

        $this->assertEquals($originalCreatedAt->format('Y-m-d H:i:s'), $task->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals($originalUpdatedAt->format('Y-m-d H:i:s'), $task->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_command_processes_multiple_tasks_with_limit(): void
    {
        // Создаем несколько задач с одинаковыми timestamps
        $tasks = Task::factory()->count(5)->create([
            'gid' => function () {
                return '12345678' . rand(10, 99);
            },
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        // Мокаем AsanaService для каждой задачи
        $mockService = Mockery::mock(AsanaService::class);

        foreach ($tasks->take(3) as $task) {
            $mockService->shouldReceive('getTaskDetails')
                ->once()
                ->with($task->gid)
                ->andReturn([
                    'gid' => $task->gid,
                    'name' => $task->title,
                    'created_at' => '2024-01-15T08:30:00.000Z',
                    'modified_at' => '2024-02-20T14:45:00.000Z',
                ]);
        }

        $this->app->instance(AsanaService::class, $mockService);

        // Запускаем команду с лимитом 3
        $exitCode = Artisan::call('asana:update-timestamps', [
            '--limit' => 3,
        ]);

        $this->assertEquals(0, $exitCode);

        // Проверяем, что обработаны только первые 3 задачи
        $updatedCount = Task::whereNotNull('gid')
            ->whereRaw('created_at != updated_at')
            ->count();

        $this->assertGreaterThanOrEqual(3, $updatedCount);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

