<?php

namespace Tests\Unit\Policies;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\CommentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CommentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CommentPolicy();
    }

    public function test_view_any_returns_true(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_permissions_product_manager_can_view(): void
    {
        $pm = User::factory()->create();
        $pmRole = Role::create(['name' => 'Product Manager']);
        $pm->assignRole($pmRole);

        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->assertTrue($this->policy->view($pm, $comment));
    }

    public function test_view_permissions_project_member_can_view(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id, ['role' => 'Developer']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->assertTrue($this->policy->view($member, $comment));
    }

    public function test_view_permissions_non_project_member_cannot_view(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->assertFalse($this->policy->view($nonMember, $comment));
    }

    public function test_create_permissions_product_manager_can_create(): void
    {
        $pm = User::factory()->create();
        $pmRole = Role::create(['name' => 'Product Manager']);
        $pm->assignRole($pmRole);

        $this->assertTrue($this->policy->create($pm));
    }

    public function test_create_permissions_developer_can_create(): void
    {
        $developer = User::factory()->create();
        $devRole = Role::create(['name' => 'Developer']);
        $developer->assignRole($devRole);

        $this->assertTrue($this->policy->create($developer));
    }

    public function test_create_permissions_viewer_cannot_create(): void
    {
        $viewer = User::factory()->create();
        $viewerRole = Role::create(['name' => 'Viewer']);
        $viewer->assignRole($viewerRole);

        $this->assertFalse($this->policy->create($viewer));
    }

    public function test_update_permissions_product_manager_can_update(): void
    {
        $pm = User::factory()->create();
        $pmRole = Role::create(['name' => 'Product Manager']);
        $pm->assignRole($pmRole);

        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->assertTrue($this->policy->update($pm, $comment));
    }

    public function test_update_permissions_author_can_update_own_comment(): void
    {
        $author = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $author->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $author->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $author->id,
        ]);

        $this->assertTrue($this->policy->update($author, $comment));
    }

    public function test_update_permissions_non_author_cannot_update(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $author->id]);
        $project->members()->attach($otherUser->id, ['role' => 'Developer']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $author->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $author->id,
        ]);

        $this->assertFalse($this->policy->update($otherUser, $comment));
    }

    public function test_delete_permissions_product_manager_can_delete(): void
    {
        $pm = User::factory()->create();
        $pmRole = Role::create(['name' => 'Product Manager']);
        $pm->assignRole($pmRole);

        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->assertTrue($this->policy->delete($pm, $comment));
    }

    public function test_delete_permissions_author_can_delete_own_comment(): void
    {
        $author = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $author->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $author->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $author->id,
        ]);

        $this->assertTrue($this->policy->delete($author, $comment));
    }

    public function test_delete_permissions_non_author_cannot_delete(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $author->id]);
        $project->members()->attach($otherUser->id, ['role' => 'Developer']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $author->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $author->id,
        ]);

        $this->assertFalse($this->policy->delete($otherUser, $comment));
    }

    public function test_restore_permissions_product_manager_can_restore(): void
    {
        $pm = User::factory()->create();
        $pmRole = Role::create(['name' => 'Product Manager']);
        $pm->assignRole($pmRole);

        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->assertTrue($this->policy->restore($pm, $comment));
    }

    public function test_restore_permissions_developer_cannot_restore(): void
    {
        $developer = User::factory()->create();
        $devRole = Role::create(['name' => 'Developer']);
        $developer->assignRole($devRole);

        $project = Project::factory()->create(['owner_id' => $developer->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $developer->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $developer->id,
        ]);

        $this->assertFalse($this->policy->restore($developer, $comment));
    }

    public function test_force_delete_permissions_product_manager_can_force_delete(): void
    {
        $pm = User::factory()->create();
        $pmRole = Role::create(['name' => 'Product Manager']);
        $pm->assignRole($pmRole);

        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->assertTrue($this->policy->forceDelete($pm, $comment));
    }

    public function test_force_delete_permissions_developer_cannot_force_delete(): void
    {
        $developer = User::factory()->create();
        $devRole = Role::create(['name' => 'Developer']);
        $developer->assignRole($devRole);

        $project = Project::factory()->create(['owner_id' => $developer->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $developer->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $developer->id,
        ]);

        $this->assertFalse($this->policy->forceDelete($developer, $comment));
    }
}
