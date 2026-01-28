<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Document;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_are_deleted_when_project_is_deleted(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $task1 = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('tasks', ['id' => $task1->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id]);

        $project->delete();

        $this->assertDatabaseMissing('tasks', ['id' => $task1->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task2->id]);
    }

    public function test_project_members_are_detached_when_project_is_deleted(): void
    {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $project->members()->attach($member1->id, ['role' => 'Developer']);
        $project->members()->attach($member2->id, ['role' => 'Viewer']);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $member1->id,
        ]);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $member2->id,
        ]);

        $project->delete();

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $member1->id,
        ]);

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $member2->id,
        ]);
    }

    public function test_project_invitations_are_deleted_when_project_is_deleted(): void
    {
        $owner = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $invitation1 = ProjectInvitation::factory()->create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => 'user1@example.com',
        ]);

        $invitation2 = ProjectInvitation::factory()->create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => 'user2@example.com',
        ]);

        $this->assertDatabaseHas('project_invitations', ['id' => $invitation1->id]);
        $this->assertDatabaseHas('project_invitations', ['id' => $invitation2->id]);

        $project->delete();

        $this->assertDatabaseMissing('project_invitations', ['id' => $invitation1->id]);
        $this->assertDatabaseMissing('project_invitations', ['id' => $invitation2->id]);
    }

    public function test_comments_are_deleted_when_project_is_deleted_via_tasks(): void
    {
        $owner = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $comment1 = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $comment2 = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->assertDatabaseHas('comments', ['id' => $comment1->id]);
        $this->assertDatabaseHas('comments', ['id' => $comment2->id]);

        $project->delete();

        // Comments should be deleted because tasks are deleted (cascade)
        $this->assertDatabaseMissing('comments', ['id' => $comment1->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment2->id]);
    }

    public function test_project_owner_can_delete_project(): void
    {
        $owner = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $projectId = $project->id;

        $project->delete();

        $this->assertDatabaseMissing('projects', ['id' => $projectId]);
    }

    public function test_non_owner_cannot_delete_project(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id, ['role' => 'Developer']);

        $this->assertFalse($member->can('delete', $project));
    }

    public function test_deleting_project_with_active_tasks(): void
    {
        $owner = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $task1 = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'status' => 'todo',
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'status' => 'In Progress',
        ]);

        $task3 = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'status' => 'Done',
        ]);

        $this->assertCount(3, $project->tasks);

        $project->delete();

        $this->assertDatabaseMissing('tasks', ['id' => $task1->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task2->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task3->id]);
    }

    public function test_deleting_project_with_linked_documents(): void
    {
        $owner = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $document1 = Document::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $document2 = Document::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $this->assertDatabaseHas('documents', ['id' => $document1->id]);
        $this->assertDatabaseHas('documents', ['id' => $document2->id]);

        $project->delete();

        // Documents should be deleted based on cascade (nullOnDelete in migration)
        // Actually the migration uses nullOnDelete, so documents are NOT deleted
        // Let's verify the actual behavior
        $this->assertDatabaseHas('documents', ['id' => $document1->id, 'project_id' => null]);
        $this->assertDatabaseHas('documents', ['id' => $document2->id, 'project_id' => null]);
    }

    public function test_deleting_project_with_pending_invitations(): void
    {
        $owner = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $pendingInvitation = ProjectInvitation::factory()->create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => 'pending@example.com',
            'status' => 'pending',
        ]);

        $acceptedInvitation = ProjectInvitation::factory()->create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => 'accepted@example.com',
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('project_invitations', ['id' => $pendingInvitation->id]);
        $this->assertDatabaseHas('project_invitations', ['id' => $acceptedInvitation->id]);

        $project->delete();

        $this->assertDatabaseMissing('project_invitations', ['id' => $pendingInvitation->id]);
        $this->assertDatabaseMissing('project_invitations', ['id' => $acceptedInvitation->id]);
    }

    public function test_deleting_project_with_tasks_and_comments_cascade(): void
    {
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

        // Verify all records exist before deletion
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);

        $project->delete();

        // Verify all records are deleted via cascade
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
