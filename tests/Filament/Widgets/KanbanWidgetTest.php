<?php

namespace Tests\Filament\Widgets;

use App\Filament\Widgets\KanbanWidget;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KanbanWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_renders_for_global_context(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(KanbanWidget::class)
            ->assertStatus(200);
    }

    public function test_widget_loads_project_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
        ]);

        $this->actingAs($user);

        Livewire::test(KanbanWidget::class, ['projectSlug' => $project->slug])
            ->assertStatus(200)
            ->assertSet('project.id', $project->id);
    }

    public function test_widget_updates_task_order(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
            'order' => 0,
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(KanbanWidget::class, ['projectSlug' => $project->slug])
            ->call('updateTaskOrder', [
                ['id' => $task->id, 'status' => 'done', 'order' => 0],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'done',
            'order' => 0,
        ]);
    }

    public function test_widget_loads_default_statuses_for_global_context(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(KanbanWidget::class)
            ->assertStatus(200)
            ->assertSet('statuses', [
                'backlog',
                'todo',
                'in_progress',
                'review',
                'done',
            ]);
    }

    public function test_widget_loads_columns_with_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
            'title' => 'Test Task',
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(KanbanWidget::class, ['projectSlug' => $project->slug])
            ->assertStatus(200)
            ->assertSet('columns.todo', [
                [
                    'id' => Task::first()->id,
                    'title' => 'Test Task',
                    'description' => Task::first()->description,
                    'assigned_to' => $user->name,
                    'due_date' => null,
                    'is_overdue' => false,
                ],
            ]);
    }
}
