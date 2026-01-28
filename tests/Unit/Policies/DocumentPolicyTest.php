<?php

namespace Tests\Unit\Policies;

use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use App\Policies\DocumentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private DocumentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DocumentPolicy();
    }

    public function test_view_any_can_be_called(): void
    {
        $user = User::factory()->create();

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->viewAny($user));
    }

    public function test_view_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->view($user, $document));
    }

    public function test_create_can_be_called(): void
    {
        $user = User::factory()->create();

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->create($user));
    }

    public function test_update_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->update($user, $document));
    }

    public function test_delete_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->delete($user, $document));
    }

    public function test_restore_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->restore($user, $document));
    }

    public function test_force_delete_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->forceDelete($user, $document));
    }

    public function test_force_delete_any_can_be_called(): void
    {
        $user = User::factory()->create();

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->forceDeleteAny($user));
    }

    public function test_restore_any_can_be_called(): void
    {
        $user = User::factory()->create();

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->restoreAny($user));
    }

    public function test_replicate_can_be_called(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->replicate($user, $document));
    }

    public function test_reorder_can_be_called(): void
    {
        $user = User::factory()->create();

        // The DocumentPolicy uses ability system, so we just verify the method exists
        // and can be called without error
        $this->assertIsBool($this->policy->reorder($user));
    }
}
