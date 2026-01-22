<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_kanban_scope_orders_by_status_and_order()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
            'order' => 2,
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
            'order' => 1,
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'done',
            'order' => 99,
        ]);

        $tasks = Task::forKanban($project)->get();

        // Should be ordered by status first, then by order within status
        // 'done' comes before 'todo' alphabetically
        $this->assertEquals('done', $tasks->first()->status);
        $this->assertEquals(99, $tasks->first()->order);

        // Within 'todo' status, should be ordered by order column
        $todoTasks = $tasks->filter(fn ($task) => $task->status === 'todo');
        $this->assertEquals(1, $todoTasks->first()->order);
        $this->assertEquals(2, $todoTasks->last()->order);
    }

    public function test_for_global_kanban_scope_includes_all_tasks()
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['owner_id' => $user->id]);
        $project2 = Project::factory()->create(['owner_id' => $user->id]);

        Task::factory()->create(['project_id' => $project1->id, 'status' => 'todo']);
        Task::factory()->create(['project_id' => $project2->id, 'status' => 'done']);

        $tasks = Task::forGlobalKanban()->get();

        $this->assertCount(2, $tasks);
    }
}
