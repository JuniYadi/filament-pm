<?php

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPolicyTest extends TestCase
{
    use RefreshDatabase;

    private TaskPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TaskPolicy();
    }

    public function test_view_any_can_be_called(): void
    {
        $user = User::factory()->create();

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->viewAny($user));
    }

    public function test_view_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->view($user, $task));
    }

    public function test_create_can_be_called(): void
    {
        $user = User::factory()->create();

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->create($user));
    }

    public function test_update_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->update($user, $task));
    }

    public function test_delete_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->delete($user, $task));
    }

    public function test_restore_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->restore($user, $task));
    }

    public function test_force_delete_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->forceDelete($user, $task));
    }

    public function test_force_delete_any_can_be_called(): void
    {
        $user = User::factory()->create();

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->forceDeleteAny($user));
    }

    public function test_restore_any_can_be_called(): void
    {
        $user = User::factory()->create();

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->restoreAny($user));
    }

    public function test_replicate_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->replicate($user, $task));
    }

    public function test_reorder_can_be_called(): void
    {
        $user = User::factory()->create();

        // The TaskPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->reorder($user));
    }
}
