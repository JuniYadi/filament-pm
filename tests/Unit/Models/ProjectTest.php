<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_kanban_statuses_returns_default_when_workflow_null()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'owner_id' => $user->id,
            'status_workflow' => null,
        ]);

        $statuses = $project->getKanbanStatuses();

        $this->assertCount(5, $statuses);
        $this->assertEquals(['backlog', 'todo', 'in_progress', 'review', 'done'], $statuses);
    }

    public function test_get_kanban_statuses_returns_custom_workflow()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'owner_id' => $user->id,
            'status_workflow' => ['planning', 'development', 'testing', 'deployed'],
        ]);

        $statuses = $project->getKanbanStatuses();

        $this->assertCount(4, $statuses);
        $this->assertEquals(['planning', 'development', 'testing', 'deployed'], $statuses);
    }
}
