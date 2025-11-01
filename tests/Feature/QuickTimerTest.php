<?php

namespace Tests\Feature;

use App\Livewire\QuickTimer;
use App\Models\Task;
use App\Models\Time;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickTimerTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_timer_component_can_be_rendered(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QuickTimer::class)
            ->assertStatus(200)
            ->assertSee('00:00:00');
    }

    public function test_user_can_start_quick_timer(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QuickTimer::class)
            ->set('title', 'Тестовий трекінг')
            ->call('startTimer')
            ->assertSet('isRunning', true)
            ->assertSet('isPaused', false);

        $this->assertDatabaseHas('times', [
            'user_id' => $user->id,
            'task_id' => null,
            'title' => 'Тестовий трекінг',
            'status' => Time::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_user_can_pause_quick_timer(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(QuickTimer::class)
            ->set('title', 'Тестовий трекінг')
            ->call('startTimer')
            ->set('seconds', 300)
            ->call('pauseTimer')
            ->assertSet('isRunning', false)
            ->assertSet('isPaused', true);

        $time = Time::where('user_id', $user->id)->first();
        $this->assertEquals(300, $time->duration);
    }

    public function test_user_can_stop_quick_timer(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QuickTimer::class)
            ->set('title', 'Тестовий трекінг')
            ->call('startTimer')
            ->set('seconds', 600)
            ->call('stopTimer');

        $this->assertDatabaseHas('times', [
            'user_id' => $user->id,
            'task_id' => null,
            'duration' => 600,
            'status' => Time::STATUS_COMPLETED,
        ]);
    }

    public function test_user_can_convert_quick_timer_to_task(): void
    {
        $user = User::factory()->create();
        $project = \App\Models\Project::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $time = Time::create([
            'user_id' => $user->id,
            'task_id' => null,
            'title' => 'Швидкий трекінг',
            'description' => 'Тест',
            'coefficient' => Time::COEFFICIENT_STANDARD,
            'duration' => 300,
            'status' => Time::STATUS_IN_PROGRESS,
            'report_status' => 'not_submitted',
            'is_archived' => false,
        ]);

        Livewire::actingAs($user)
            ->test(QuickTimer::class)
            ->set('activeTime', $time)
            ->set('timeId', $time->id)
            ->set('seconds', 300)
            ->set('isPaused', true)
            ->call('showConvertToTask')
            ->assertSet('showConvertForm', true)
            ->set('selectedProjectId', $project->id)
            ->call('updatedSelectedProjectId', $project->id)
            ->assertCount('availableTasks', 1)
            ->set('selectedTaskId', $task->id)
            ->call('convertToTask');

        $time->refresh();
        $this->assertEquals($task->id, $time->task_id);
    }
}
