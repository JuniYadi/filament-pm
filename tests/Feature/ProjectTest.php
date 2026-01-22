<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'owner_id' => $user->id,
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'owner_id' => $user->id,
            'name' => $project->name,
        ]);
    }

    public function test_project_has_slug(): void
    {
        $project = Project::factory()->create([
            'name' => 'My Test Project',
            'slug' => 'my-test-project',
        ]);

        $this->assertEquals('my-test-project', $project->slug);
    }

    public function test_project_owner_can_view_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('view', $project));
    }

    public function test_project_member_can_view_project(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id, ['role' => 'Developer']);

        $this->assertTrue($member->can('view', $project));
    }

    public function test_non_member_cannot_view_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->assertFalse($user->can('view', $project));
    }

    public function test_project_owner_can_update_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('update', $project));
    }

    public function test_project_member_cannot_update_project(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id, ['role' => 'Developer']);

        $this->assertFalse($member->can('update', $project));
    }

    public function test_project_owner_can_delete_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('delete', $project));
    }

    public function test_project_has_status_workflow(): void
    {
        $workflow = ['Backlog', 'In Progress', 'Review', 'Done'];
        $project = Project::factory()->create([
            'status_workflow' => $workflow,
        ]);

        $this->assertEquals($workflow, $project->status_workflow);
    }

    public function test_project_belongs_to_owner(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $this->assertInstanceOf(User::class, $project->owner);
        $this->assertEquals($user->id, $project->owner->id);
    }

    public function test_project_has_many_members(): void
    {
        $project = Project::factory()->create();
        $members = User::factory()->count(3)->create();

        $project->members()->attach($members->pluck('id'), ['role' => 'Developer']);

        $this->assertCount(3, $project->members);
    }

    public function test_project_has_many_tasks(): void
    {
        $project = Project::factory()->create();

        $task1 = $project->tasks()->create([
            'title' => 'Task 1',
            'created_by' => $project->owner_id,
        ]);

        $task2 = $project->tasks()->create([
            'title' => 'Task 2',
            'created_by' => $project->owner_id,
        ]);

        $this->assertCount(2, $project->tasks);
        $this->assertTrue($project->tasks->contains($task1));
        $this->assertTrue($project->tasks->contains($task2));
    }
}
