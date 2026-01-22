<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'project_id' => $project->id,
            'title' => $task->title,
        ]);
    }

    public function test_task_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $task->project);
        $this->assertEquals($project->id, $task->project->id);
    }

    public function test_task_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $task->createdBy);
        $this->assertEquals($user->id, $task->createdBy->id);
    }

    public function test_task_can_be_assigned_to_user(): void
    {
        $assignee = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $assignee->id]);

        $this->assertInstanceOf(User::class, $task->assignedTo);
        $this->assertEquals($assignee->id, $task->assignedTo->id);
    }

    public function test_task_has_status(): void
    {
        $task = Task::factory()->create(['status' => 'In Progress']);

        $this->assertEquals('In Progress', $task->status);
    }

    public function test_task_has_order(): void
    {
        $task1 = Task::factory()->create(['order' => 1]);
        $task2 = Task::factory()->create(['order' => 2]);

        $this->assertEquals(1, $task1->order);
        $this->assertEquals(2, $task2->order);
    }

    public function test_task_has_many_comments(): void
    {
        $task = Task::factory()->create();
        $user = User::factory()->create();

        $task->comments()->createMany([
            ['user_id' => $user->id, 'content' => 'First comment'],
            ['user_id' => $user->id, 'content' => 'Second comment'],
        ]);

        $this->assertCount(2, $task->comments);
    }

    public function test_project_member_can_view_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id, ['role' => 'Developer']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $this->assertTrue($member->can('view', $task));
    }

    public function test_assigned_user_can_view_task(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'assigned_to' => $assignee->id,
        ]);

        $this->assertTrue($assignee->can('view', $task));
    }

    public function test_non_project_member_cannot_view_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->assertFalse($user->can('view', $task));
    }

    public function test_project_member_can_create_task(): void
    {
        $developerRole = Role::create(['name' => 'Developer']);
        $member = User::factory()->create();
        $member->assignRole($developerRole);

        $this->assertTrue($member->can('create', Task::class));
    }

    public function test_non_project_member_cannot_create_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->assertFalse($user->can('create', [Task::class, $project]));
    }

    public function test_project_member_can_update_task(): void
    {
        $pmRole = Role::create(['name' => 'Product Manager']);
        $member = User::factory()->create();
        $member->assignRole($pmRole);

        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->assertTrue($member->can('update', $task));
    }

    public function test_task_creator_can_update_task(): void
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $project->owner_id,
        ]);

        $this->assertTrue($project->owner->can('update', $task));
    }

    public function test_project_member_can_delete_task(): void
    {
        $pmRole = Role::create(['name' => 'Product Manager']);
        $member = User::factory()->create();
        $member->assignRole($pmRole);

        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->assertTrue($member->can('delete', $task));
    }

    public function test_developer_cannot_delete_task(): void
    {
        $devRole = Role::create(['name' => 'Developer']);
        $member = User::factory()->create();
        $member->assignRole($devRole);

        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->assertFalse($member->can('delete', $task));
    }
}
