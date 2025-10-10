<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskCommentsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_task_can_have_comments(): void
    {
        $user = User::first() ?? User::factory()->create();
        $project = Project::first();

        // Создаем задачу
        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => Task::STATUS_NEW,
            'priority' => Task::PRIORITY_LOW,
            'project_id' => $project->id,
            'is_completed' => false,
            'budget' => 10,
            'spent' => 0,
            'progress' => 0,
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => 'Test comment',
            'asana_gid' => 'test_gid_123',
        ]);

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => 'Test comment',
            'asana_gid' => 'test_gid_123',
        ]);

        $this->assertEquals(1, $task->comments()->count());
        $this->assertEquals('Test comment', $task->comments->first()->content);
    }

    public function test_comment_belongs_to_task_and_user(): void
    {
        $user = User::first() ?? User::factory()->create();
        $project = Project::first();

        $task = Task::create([
            'title' => 'Test Task 2',
            'description' => 'Test Description',
            'status' => Task::STATUS_NEW,
            'priority' => Task::PRIORITY_LOW,
            'project_id' => $project->id,
            'is_completed' => false,
            'budget' => 10,
            'spent' => 0,
            'progress' => 0,
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => 'Test comment 2',
        ]);

        $this->assertEquals($task->id, $comment->task->id);
        $this->assertEquals($user->id, $comment->user->id);
    }
}
