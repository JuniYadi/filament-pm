<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Task;
use Filament\Widgets\Widget;

class KanbanWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.kanban-board';

    protected int|string|array $columnSpan = 'full';

    public ?Project $project = null;

    public ?string $projectSlug = null;

    public array $columns = [];

    public array $statuses = [];

    public function mount(?string $projectSlug = null): void
    {
        $this->projectSlug = $projectSlug;

        if ($projectSlug) {
            $this->project = Project::where('slug', $projectSlug)->firstOrFail();
            $this->statuses = $this->project->getKanbanStatuses();
        } else {
            $this->statuses = collect(\App\Enums\TaskStatus::ordered())
                ->map(fn ($status) => $status->value)
                ->toArray();
        }

        $this->loadColumns();
    }

    protected function loadColumns(): void
    {
        $query = $this->project
            ? Task::forKanban($this->project)->with(['assignedTo'])
            : Task::forGlobalKanban()->with(['assignedTo']);

        $tasks = $query->get();

        foreach ($this->statuses as $status) {
            $this->columns[$status] = $tasks
                ->where('status', $status)
                ->map(fn ($task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'assigned_to' => $task->assignedTo?->name,
                ])
                ->values()
                ->toArray();
        }
    }

    public function updateTaskStatus(int $taskId, string $status): void
    {
        $task = Task::find($taskId);
        if ($task && $this->canUpdateTask($task)) {
            $task->update(['status' => $status]);
            $this->loadColumns();
        }
    }

    public function updateTaskOrder(array $tasks): void
    {
        foreach ($tasks as $index => $taskData) {
            $task = Task::find($taskData['id']);
            if ($task && $this->canUpdateTask($task)) {
                $task->update([
                    'status' => $taskData['status'],
                    'order' => $index,
                ]);
            }
        }

        $this->loadColumns();
    }

    protected function canUpdateTask(Task $task): bool
    {
        return auth()->check() && auth()->user()->can('update', $task);
    }
}
