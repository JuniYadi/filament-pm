<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    // Basic CRUD Tests

    public function test_comment_can_be_created_by_project_member(): void
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
            'user_id' => $member->id,
            'content' => 'Test comment',
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'task_id' => $task->id,
            'user_id' => $member->id,
            'content' => 'Test comment',
        ]);
    }

    public function test_comment_creation_denied_for_non_project_member(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();

        $viewerRole = Role::create(['name' => 'Viewer']);
        $nonMember->assignRole($viewerRole);

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $this->assertFalse($nonMember->can('create', Comment::class));
    }

    public function test_comment_can_be_created_by_task_assignee(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();

        $developerRole = Role::create(['name' => 'Developer']);
        $assignee->assignRole($developerRole);

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'assigned_to' => $assignee->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'content' => 'Comment from assignee',
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'task_id' => $task->id,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_comment_can_be_edited_by_author(): void
    {
        $user = User::factory()->create();
        $developerRole = Role::create(['name' => 'Developer']);
        $user->assignRole($developerRole);

        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => 'Original content',
        ]);

        $this->assertTrue($user->can('update', $comment));

        $comment->update(['content' => 'Updated content']);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_comment_editing_denied_for_non_author(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $developerRole = Role::create(['name' => 'Developer']);
        $author->assignRole($developerRole);
        $otherUser->assignRole($developerRole);

        $project = Project::factory()->create(['owner_id' => $author->id]);
        $project->members()->attach($otherUser->id, ['role' => 'Developer']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $author->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $author->id,
            'content' => 'Author comment',
        ]);

        $this->assertFalse($otherUser->can('update', $comment));
    }

    public function test_comment_can_be_deleted_by_author(): void
    {
        $user = User::factory()->create();
        $developerRole = Role::create(['name' => 'Developer']);
        $user->assignRole($developerRole);

        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($user->can('delete', $comment));

        $comment->delete();

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_comment_deletion_denied_for_non_author(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $developerRole = Role::create(['name' => 'Developer']);
        $author->assignRole($developerRole);
        $otherUser->assignRole($developerRole);

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

        $this->assertFalse($otherUser->can('delete', $comment));
    }

    public function test_product_manager_can_edit_any_comment(): void
    {
        $pm = User::factory()->create();
        $developer = User::factory()->create();
        $pmRole = Role::create(['name' => 'Product Manager']);
        $devRole = Role::create(['name' => 'Developer']);
        $pm->assignRole($pmRole);
        $developer->assignRole($devRole);

        $project = Project::factory()->create(['owner_id' => $pm->id]);
        $project->members()->attach($developer->id, ['role' => 'Developer']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $pm->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $developer->id,
            'content' => 'Developer comment',
        ]);

        $this->assertTrue($pm->can('update', $comment));

        $comment->update(['content' => 'Updated by PM']);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated by PM',
        ]);
    }

    public function test_product_manager_can_delete_any_comment(): void
    {
        $pm = User::factory()->create();
        $developer = User::factory()->create();
        $pmRole = Role::create(['name' => 'Product Manager']);
        $devRole = Role::create(['name' => 'Developer']);
        $pm->assignRole($pmRole);
        $developer->assignRole($devRole);

        $project = Project::factory()->create(['owner_id' => $pm->id]);
        $project->members()->attach($developer->id, ['role' => 'Developer']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $pm->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $developer->id,
        ]);

        $this->assertTrue($pm->can('delete', $comment));

        $comment->delete();

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    // Relationship Tests

    public function test_comment_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $developerRole = Role::create(['name' => 'Developer']);
        $user->assignRole($developerRole);

        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }

    public function test_comment_belongs_to_task(): void
    {
        $user = User::factory()->create();
        $developerRole = Role::create(['name' => 'Developer']);
        $user->assignRole($developerRole);

        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Task::class, $comment->task);
        $this->assertEquals($task->id, $comment->task->id);
    }

    public function test_task_has_many_comments(): void
    {
        $user = User::factory()->create();
        $developerRole = Role::create(['name' => 'Developer']);
        $user->assignRole($developerRole);

        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        Comment::factory()->count(3)->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(3, $task->comments);
        foreach ($task->comments as $comment) {
            $this->assertEquals($task->id, $comment->task_id);
        }
    }

    // Timestamp Tests

    public function test_comment_has_created_at_timestamp(): void
    {
        $user = User::factory()->create();
        $developerRole = Role::create(['name' => 'Developer']);
        $user->assignRole($developerRole);

        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($comment->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $comment->created_at);
    }

    public function test_comment_has_updated_at_timestamp(): void
    {
        $user = User::factory()->create();
        $developerRole = Role::create(['name' => 'Developer']);
        $user->assignRole($developerRole);

        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => 'Original content',
        ]);

        $originalUpdatedAt = $comment->updated_at;

        sleep(1); // Ensure timestamp difference

        $comment->update(['content' => 'Updated content']);

        $this->assertNotEquals($originalUpdatedAt, $comment->fresh()->updated_at);
    }

    // Authorization Tests (via Policy)

    public function test_comment_authorization_view_permissions(): void
    {
        $pm = User::factory()->create();
        $member = User::factory()->create();
        $nonMember = User::factory()->create();

        $pmRole = Role::create(['name' => 'Product Manager']);
        $devRole = Role::create(['name' => 'Developer']);

        $pm->assignRole($pmRole);
        $member->assignRole($devRole);

        $project = Project::factory()->create(['owner_id' => $pm->id]);
        $project->members()->attach($member->id, ['role' => 'Developer']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $pm->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $pm->id,
        ]);

        // Product Manager can view
        $this->assertTrue($pm->can('view', $comment));

        // Project member can view
        $this->assertTrue($member->can('view', $comment));

        // Non-project member cannot view
        $this->assertFalse($nonMember->can('view', $comment));
    }

    public function test_comment_authorization_create_permissions(): void
    {
        $pm = User::factory()->create();
        $developer = User::factory()->create();
        $viewer = User::factory()->create();

        $pmRole = Role::create(['name' => 'Product Manager']);
        $devRole = Role::create(['name' => 'Developer']);
        $viewerRole = Role::create(['name' => 'Viewer']);

        $pm->assignRole($pmRole);
        $developer->assignRole($devRole);
        $viewer->assignRole($viewerRole);

        // PM can create
        $this->assertTrue($pm->can('create', Comment::class));

        // Developer can create
        $this->assertTrue($developer->can('create', Comment::class));

        // Viewer cannot create
        $this->assertFalse($viewer->can('create', Comment::class));
    }

    public function test_comment_authorization_update_permissions(): void
    {
        $pm = User::factory()->create();
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $pmRole = Role::create(['name' => 'Product Manager']);
        $devRole = Role::create(['name' => 'Developer']);

        $pm->assignRole($pmRole);
        $author->assignRole($devRole);
        $otherUser->assignRole($devRole);

        $project = Project::factory()->create(['owner_id' => $pm->id]);
        $project->members()->attach($author->id, ['role' => 'Developer']);
        $project->members()->attach($otherUser->id, ['role' => 'Developer']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $pm->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $author->id,
        ]);

        // PM can update any comment
        $this->assertTrue($pm->can('update', $comment));

        // Author can update their own comment
        $this->assertTrue($author->can('update', $comment));

        // Other user cannot update
        $this->assertFalse($otherUser->can('update', $comment));
    }

    public function test_comment_authorization_delete_permissions(): void
    {
        $pm = User::factory()->create();
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $pmRole = Role::create(['name' => 'Product Manager']);
        $devRole = Role::create(['name' => 'Developer']);

        $pm->assignRole($pmRole);
        $author->assignRole($devRole);
        $otherUser->assignRole($devRole);

        $project = Project::factory()->create(['owner_id' => $pm->id]);
        $project->members()->attach($author->id, ['role' => 'Developer']);
        $project->members()->attach($otherUser->id, ['role' => 'Developer']);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $pm->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $author->id,
        ]);

        // PM can delete any comment
        $this->assertTrue($pm->can('delete', $comment));

        // Author can delete their own comment
        $this->assertTrue($author->can('delete', $comment));

        // Other user cannot delete
        $this->assertFalse($otherUser->can('delete', $comment));
    }
}
