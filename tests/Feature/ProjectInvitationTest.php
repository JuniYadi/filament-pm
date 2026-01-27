<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProjectInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_can_be_created(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => 'newuser@example.com',
            'role' => 'Developer',
            'token' => \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->assertDatabaseHas('project_invitations', [
            'email' => 'newuser@example.com',
            'project_id' => $project->id,
            'status' => 'pending',
        ]);
    }

    public function test_invitation_expires_correctly(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $expiredInvitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => 'expired@example.com',
            'role' => 'Developer',
            'token' => \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'invited_by' => $owner->id,
            'expires_at' => now()->subDays(1),
        ]);

        $validInvitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => 'valid@example.com',
            'role' => 'Developer',
            'token' => \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->assertTrue($expiredInvitation->isExpired());
        $this->assertFalse($validInvitation->isExpired());
    }

    public function test_invitation_acceptance_adds_user_to_project(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $user = User::factory()->create(['email' => 'newmember@example.com']);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => 'newmember@example.com',
            'role' => 'Developer',
            'token' => \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->assertFalse($project->members()->where('user_id', $user->id)->exists());

        $invitation->accept($user);

        $this->assertTrue($project->members()->where('user_id', $user->id)->exists());
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'Developer',
        ]);
        $this->assertEquals('accepted', $invitation->fresh()->status);
    }

    public function test_invitation_can_be_declined(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => 'decline@example.com',
            'role' => 'Developer',
            'token' => \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $invitation->decline();

        $this->assertEquals('declined', $invitation->fresh()->status);
    }

    public function test_project_policy_only_allows_owners_to_add_members(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($owner->can('addMember', $project));
        $this->assertFalse($nonOwner->can('addMember', $project));
    }

    public function test_project_policy_allows_inviting_members(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($owner->can('inviteMember', $project));
        $this->assertFalse($nonOwner->can('inviteMember', $project));
    }

    public function test_project_policy_prevents_owner_from_removing_themselves(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($owner->can('removeMember', [$project, $owner]));
    }

    public function test_project_policy_allows_owner_to_remove_other_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $project->members()->attach($member->id, ['role' => 'Developer']);

        $this->assertTrue($owner->can('removeMember', [$project, $member]));
    }

    public function test_invitation_show_page_renders(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => 'invite@example.com',
            'role' => 'Developer',
            'token' => \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->get(route('invitations.show', $invitation->token));

        $response->assertStatus(200);
        $response->assertViewHas('invitation');
    }

    public function test_expired_invitation_returns_410(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => 'expired@example.com',
            'role' => 'Developer',
            'token' => \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'invited_by' => $owner->id,
            'expires_at' => now()->subDays(1),
        ]);

        $response = $this->get(route('invitations.show', $invitation->token));

        $response->assertStatus(410);
    }

    public function test_authenticated_user_can_accept_invitation(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create(['email' => 'newmember@example.com']);
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => 'newmember@example.com',
            'role' => 'Developer',
            'token' => \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($user)
            ->post(route('invitations.accept', $invitation->token));

        // User should be added to project
        $this->assertTrue($project->members()->where('user_id', $user->id)->exists());
        // Invitation should be marked as accepted
        $this->assertEquals('accepted', $invitation->fresh()->status);
    }
}
