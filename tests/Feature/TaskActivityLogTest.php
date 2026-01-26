<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TaskActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_creation_is_logged(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $task = Task::factory()->create([
            'title' => 'Test Task',
            'status' => 'todo',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'created',
        ]);
    }

    public function test_status_change_is_logged(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $task = Task::factory()->create(['status' => 'todo']);
        $task->update(['status' => 'in_progress']);

        $log = ActivityLog::where('task_id', $task->id)
            ->where('action', 'status_changed')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('todo', $log->old_values['status']);
        $this->assertEquals('in_progress', $log->new_values['status']);
    }

    public function test_assignee_change_is_logged(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();
        Auth::login($user);

        $task = Task::factory()->create(['assigned_to' => null]);
        $task->update(['assigned_to' => $assignee->id]);

        $log = ActivityLog::where('task_id', $task->id)
            ->where('action', 'assigned')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($assignee->id, $log->new_values['assigned_to']);
    }

    public function test_task_deletion_is_logged(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $task = Task::factory()->create(['title' => 'To Be Deleted']);
        $taskId = $task->id;

        // Verify the task was created (activity log should exist)
        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $taskId,
            'action' => 'created',
        ]);

        $task->delete();

        // After deletion, all activity logs are cascade deleted
        $this->assertDatabaseMissing('activity_logs', [
            'task_id' => $taskId,
        ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $taskId,
        ]);
    }

    public function test_activity_logs_are_accessible_from_task(): void
    {
        $task = Task::factory()->create();
        // The task creation creates 1 log, plus 3 from factory = 4 total
        ActivityLog::factory()->count(3)->create(['task_id' => $task->id]);

        $this->assertCount(4, $task->activityLogs);
        $this->assertInstanceOf(ActivityLog::class, $task->activityLogs->first());
    }
}
