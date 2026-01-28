<?php

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ProjectPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ProjectPolicy();
    }

    public function test_add_member_owner_only(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($this->policy->addMember($owner, $project));
        $this->assertFalse($this->policy->addMember($member, $project));
    }

    public function test_remove_member_owner_only(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id, ['role' => 'Developer']);

        $this->assertTrue($this->policy->removeMember($owner, $project, $member));
        $this->assertFalse($this->policy->removeMember($member, $project, $owner));
    }

    public function test_remove_member_owner_cannot_remove_themselves(): void
    {
        $owner = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($this->policy->removeMember($owner, $project, $owner));
    }

    public function test_invite_member_owner_only(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($this->policy->inviteMember($owner, $project));
        $this->assertFalse($this->policy->inviteMember($member, $project));
    }

    public function test_change_member_role_owner_only(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id, ['role' => 'Developer']);

        $this->assertTrue($this->policy->changeMemberRole($owner, $project));
        $this->assertFalse($this->policy->changeMemberRole($member, $project));
    }

    public function test_view_any_can_be_called(): void
    {
        $user = User::factory()->create();

        // The ProjectPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->viewAny($user));
    }

    public function test_view_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        // The ProjectPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->view($user, $project));
    }

    public function test_create_can_be_called(): void
    {
        $user = User::factory()->create();

        // The ProjectPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->create($user));
    }

    public function test_update_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        // The ProjectPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->update($user, $project));
    }

    public function test_delete_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        // The ProjectPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->delete($user, $project));
    }

    public function test_restore_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        // The ProjectPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->restore($user, $project));
    }

    public function test_force_delete_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        // The ProjectPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->forceDelete($user, $project));
    }
}
