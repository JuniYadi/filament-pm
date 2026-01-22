# Kanban Board Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a Kanban board widget for project tasks with drag-and-drop functionality, supporting both per-project and global views.

**Architecture:**
- Filament Widget (Livewire-based) for Kanban board rendering
- Leverage existing `status` and `order` fields on Task model
- Use Project's `status_workflow` for custom columns or fall back to defaults
- Alpine.js for frontend drag-and-drop interactions

**Tech Stack:**
- Filament v4 Widgets
- Livewire v3
- Alpine.js
- Tailwind CSS

---

## Task 1: Create TaskStatus Enum

**Files:**
- Create: `app/Enums/TaskStatus.php`
- Test: `tests/Unit/Enums/TaskStatusTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/Enums/TaskStatusTest.php`:

```php
<?php

use App\Enums\TaskStatus;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    public function test_default_statuses_exist()
    {
        $this->assertEquals('backlog', TaskStatus::BACKLOG->value);
        $this->assertEquals('todo', TaskStatus::TODO->value);
        $this->assertEquals('in_progress', TaskStatus::IN_PROGRESS->value);
        $this->assertEquals('review', TaskStatus::REVIEW->value);
        $this->assertEquals('done', TaskStatus::DONE->value);
    }

    public function test_get_label_returns_translated_label()
    {
        $this->assertEquals('Backlog', TaskStatus::BACKLOG->getLabel());
        $this->assertEquals('To Do', TaskStatus::TODO->getLabel());
        $this->assertEquals('In Progress', TaskStatus::IN_PROGRESS->getLabel());
        $this->assertEquals('Review', TaskStatus::REVIEW->getLabel());
        $this->assertEquals('Done', TaskStatus::DONE->getLabel());
    }

    public function test_get_color_returns_color_for_status()
    {
        $this->assertEquals('gray', TaskStatus::BACKLOG->getColor());
        $this->assertEquals('warning', TaskStatus::TODO->getColor());
        $this->assertEquals('primary', TaskStatus::IN_PROGRESS->getColor());
        $this->assertEquals('info', TaskStatus::REVIEW->getColor());
        $this->assertEquals('success', TaskStatus::DONE->getColor());
    }

    public function test_get_all_statuses_ordered()
    {
        $ordered = TaskStatus::ordered();
        $this->assertEquals([
            TaskStatus::BACKLOG,
            TaskStatus::TODO,
            TaskStatus::IN_PROGRESS,
            TaskStatus::REVIEW,
            TaskStatus::DONE,
        ], $ordered);
    }

    public function test_from_value_creates_enum()
    {
        $status = TaskStatus::from('todo');
        $this->assertEquals(TaskStatus::TODO, $status);
    }

    public function test_try_from_value_returns_enum_or_null()
    {
        $status = TaskStatus::tryFrom('todo');
        $this->assertEquals(TaskStatus::TODO, $status);

        $invalid = TaskStatus::tryFrom('invalid_status');
        $this->assertNull($invalid);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=TaskStatusTest`
Expected: FAIL with "Class App\Enums\TaskStatus not found"

**Step 3: Write minimal implementation**

Create `app/Enums/TaskStatus.php`:

```php
<?php

namespace App\Enums;

enum TaskStatus: string
{
    case BACKLOG = 'backlog';
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case DONE = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::BACKLOG => 'Backlog',
            self::TODO => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::REVIEW => 'Review',
            self::DONE => 'Done',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BACKLOG => 'gray',
            self::TODO => 'warning',
            self::IN_PROGRESS => 'primary',
            self::REVIEW => 'info',
            self::DONE => 'success',
        };
    }

    /**
     * @return array<int, TaskStatus>
     */
    public static function ordered(): array
    {
        return [
            self::BACKLOG,
            self::TODO,
            self::IN_PROGRESS,
            self::REVIEW,
            self::DONE,
        ];
    }
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=TaskStatusTest`
Expected: PASS (all 6 tests pass)

**Step 5: Commit**

```bash
git add app/Enums/TaskStatus.php tests/Unit/Enums/TaskStatusTest.php
git commit -m "feat: add TaskStatus enum with default statuses"
```

---

## Task 2: Add Kanban Scopes to Task Model

**Files:**
- Modify: `app/Models/Task.php`
- Test: `tests/Unit/Models/TaskTest.php` (create if not exists)

**Step 1: Write the failing test**

Create `tests/Unit/Models/TaskTest.php`:

```php
<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_kanban_scope_orders_by_status_and_order()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
            'order' => 2,
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
            'order' => 1,
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'done',
            'order' => 99,
        ]);

        $tasks = Task::forKanban($project)->get();

        // Should be ordered by status first, then by order within status
        $this->assertEquals('done', $tasks->first()->status);
        $this->assertEquals(1, $tasks->first()->order);
    }

    public function test_for_global_kanban_scope_includes_all_tasks()
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['owner_id' => $user->id]);
        $project2 = Project::factory()->create(['owner_id' => $user->id]);

        Task::factory()->create(['project_id' => $project1->id, 'status' => 'todo']);
        Task::factory()->create(['project_id' => $project2->id, 'status' => 'done']);

        $tasks = Task::forGlobalKanban()->get();

        $this->assertCount(2, $tasks);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=TaskTest`
Expected: FAIL with "Method forKanban not found"

**Step 3: Write minimal implementation**

Read existing `app/Models/Task.php` first, then add scopes at the end of the class:

```php
    /**
     * Scope a query to get tasks for Kanban board of a specific project.
     */
    public function scopeForKanban(Builder $query, Project $project): Builder
    {
        return $query->where('project_id', $project->id)
            ->orderBy('status')
            ->orderBy('order');
    }

    /**
     * Scope a query to get tasks for global Kanban board.
     */
    public function scopeForGlobalKanban(Builder $query): Builder
    {
        return $query->with(['project', 'assignedTo'])
            ->orderBy('status')
            ->orderBy('order');
    }
```

Also add `use Illuminate\Database\Eloquent\Builder;` at the top if not present.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=TaskTest`
Expected: PASS

**Step 5: Commit**

```bash
git add app/Models/Task.php tests/Unit/Models/TaskTest.php
git commit -m "feat: add Kanban scopes to Task model"
```

---

## Task 3: Add Helper Method to Project Model

**Files:**
- Modify: `app/Models/Project.php`
- Test: `tests/Unit/Models/ProjectTest.php` (create if not exists)

**Step 1: Write the failing test**

Create `tests/Unit/Models/ProjectTest.php`:

```php
<?php

use App\Models\Project;
use App\Models\User;
use App\Enums\TaskStatus;
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
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProjectTest`
Expected: FAIL with "Method getKanbanStatuses not found"

**Step 3: Write minimal implementation**

Read `app/Models/Project.php` and add the method:

```php
use App\Enums\TaskStatus;

// Add this method to the Project class
/**
 * Get the Kanban statuses for this project.
 * Returns custom workflow if set, otherwise returns default statuses.
 *
 * @return array<int, string>
 */
public function getKanbanStatuses(): array
{
    if (empty($this->status_workflow)) {
        return collect(TaskStatus::ordered())
            ->map(fn ($status) => $status->value)
            ->toArray();
    }

    return $this->status_workflow;
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ProjectTest`
Expected: PASS

**Step 5: Commit**

```bash
git add app/Models/Project.php tests/Unit/Models/ProjectTest.php
git commit -m "feat: add getKanbanStatuses helper to Project model"
```

---

## Task 4: Create KanbanWidget Base Class

**Files:**
- Create: `app/Filament/Widgets/KanbanWidget.php`

**Step 1: Create the widget base**

Create `app/Filament/Widgets/KanbanWidget.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Task;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class KanbanWidget extends Widget
{
    protected static string $view = 'filament.widgets.kanban-board';

    protected int | string | array $columnSpan = 'full';

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
            ? Task::forKanban($this->project)
            : Task::forGlobalKanban();

        $tasks = $query->get();

        foreach ($this->statuses as $status) {
            $this->columns[$status] = $tasks
                ->where('status', $status)
                ->values()
                ->toArray();
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
        return true; // TODO: Add authorization
    }
}
```

**Step 2: Run Pint to format**

Run: `vendor/bin/pint app/Filament/Widgets/KanbanWidget.php`

**Step 3: Create the Blade view**

Create `resources/views/filament/widgets/kanban-board.blade.php`:

```blade
<div x-data="kanbanBoard({{ json_encode($statuses) }}, @entangle('columns'))"
     x-init="initBoard()"
     class="kanban-board">
    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($statuses as $status)
            <div class="kanban-column min-w-[300px] w-80 flex-shrink-0 bg-gray-50 dark:bg-gray-800 rounded-lg p-4"
                 data-status="{{ $status }}">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </h3>
                    <span class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs px-2 py-1 rounded-full">
                        {{ count($columns[$status] ?? []) }}
                    </span>
                </div>

                <div class="kanban-tasks space-y-2 min-h-[200px]"
                     data-status="{{ $status }}"
                     wire:sortable
                     wire:sortable-end="updateTaskOrder">
                    @foreach(($columns[$status] ?? []) as $task)
                        <div wire:sortable.item="{{ $task['id'] }}"
                             class="kanban-task-card bg-white dark:bg-gray-900 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-move hover:shadow-md transition-shadow">
                            <h4 class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-2">
                                {{ $task['title'] }}
                            </h4>
                            @if($task['description'])
                                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                                    {{ Str::limit($task['description'], 100) }}
                                </p>
                            @endif
                            @if($task['assigned_to'])
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-xs text-gray-500">
                                        Assigned to: {{ $task['assigned_to'] }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
    function kanbanBoard(statuses, columns) {
        return {
            statuses: statuses,
            columns: columns,

            initBoard() {
                // Initialize drag and drop if needed
                // Filament's wire:sortable handles most of this
            }
        }
    }
</script>
@endpush
```

**Step 4: Run Pint to format**

Run: `vendor/bin/pint resources/views/filament/widgets/kanban-board.blade.php` (if applicable)

**Step 5: Commit**

```bash
git add app/Filament/Widgets/KanbanWidget.php resources/views/filament/widgets/kanban-board.blade.php
git commit -m "feat: create base KanbanWidget with drag-and-drop"
```

---

## Task 5: Create Global Kanban Page

**Files:**
- Create: `app/Filament/Pages/KanbanBoard.php`
- Create: `resources/views/filament/pages/kanban-board.blade.php`

**Step 1: Create the page class**

Create `app/Filament/Pages/KanbanBoard.php`:

```php
<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\KanbanWidget;
use Filament\Pages\Page;

class KanbanBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static string $view = 'filament.pages.kanban-board';

    protected static ?string $navigationLabel = 'Kanban Board';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view any kanban board') ?? true;
    }
}
```

**Step 2: Create the Blade view**

Create `resources/views/filament/pages/kanban-board.blade.php`:

```blade
<x-filament-panels::page>
    <div class="py-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
            Kanban Board
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            View and manage tasks across all projects.
        </p>

        {{ \App\Filament\Widgets\KanbanWidget::make() }}
    </div>
</x-filament-panels::page>
```

**Step 3: Run Pint to format**

Run: `vendor/bin/pint app/Filament/Pages/KanbanBoard.php`

**Step 4: Commit**

```bash
git add app/Filament/Pages/KanbanBoard.php resources/views/filament/pages/kanban-board.blade.php
git commit -m "feat: add global Kanban board page"
```

---

## Task 6: Add Project-Specific Kanban View

**Files:**
- Create: `app/Filament/Pages/ProjectKanban.php`
- Create: `resources/views/filament/pages/project-kanban.blade.php`
- Modify: `app/Filament/Resources/ProjectResource.php`

**Step 1: Create the project Kanban page**

Create `app/Filament/Pages/ProjectKanban.php`:

```php
<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\KanbanWidget;
use App\Models\Project;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Route;

class ProjectKanban extends Page
{
    protected static string $view = 'filament.pages.project-kanban';

    protected static bool $shouldRegisterNavigation = false;

    public Project $record;

    public function mount(Project $record): void
    {
        $this->record = $record;
    }

    public static function route(): string
    {
        return '/projects/{record}/kanban';
    }
}
```

**Step 2: Create the Blade view**

Create `resources/views/filament/pages/project-kanban.blade.php`:

```blade
<x-filament-panels::page>
    <div class="py-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $record->name }} - Kanban
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $record->description }}
                </p>
            </div>
            <x-filament::link
                :href="\App\Filament\Resources\ProjectResource::getUrl('view', ['record' => $record])"
                color="gray"
            >
                Back to Project
            </x-filament::link>
        </div>

        {{ \App\Filament\Widgets\KanbanWidget::make()->projectSlug($record->slug) }}
    </div>
</x-filament-panels::page>
```

**Step 3: Add route to ProjectResource**

Read `app/Filament/Resources/ProjectResource.php` and add the page registration. Look for `getPages()` method and add:

```php
use App\Filament\Pages\ProjectKanban;

public static function getPages(): array
{
    return [
        'index' => ListProjects::route('/'),
        'create' => CreateProject::route('/create'),
        'view' => ViewProject::route('/{record}'),
        'edit' => EditProject::route('/{record}/edit'),
        'kanban' => ProjectKanban::route('/{record}/kanban'),
    ];
}
```

**Step 4: Add view action to ProjectResource**

Add to the table actions or view page actions:

```php
use Filament\Tables\Actions\Action;

Action::make('view_kanban')
    ->label('Kanban Board')
    ->icon('heroicon-o-queue-list')
    ->url(fn (Project $record) => ProjectResource::getUrl('kanban', ['record' => $record])),
```

**Step 5: Run Pint to format**

Run: `vendor/bin/pint app/Filament/Pages/ProjectKanban.php`

**Step 6: Commit**

```bash
git add app/Filament/Pages/ProjectKanban.php resources/views/filament/pages/project-kanban.blade.php app/Filament/Resources/ProjectResource.php
git commit -m "feat: add project-specific Kanban view"
```

---

## Task 7: Add Widget Tests

**Files:**
- Create: `tests/Filament/Widgets/KanbanWidgetTest.php`

**Step 1: Write feature test**

Create `tests/Filament/Widgets/KanbanWidgetTest.php`:

```php
<?php

use App\Filament\Widgets\KanbanWidget;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KanbanWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_renders_for_global_context()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(KanbanWidget::class)
            ->assertStatus(200);
    }

    public function test_widget_loads_project_tasks()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
        ]);

        $this->actingAs($user);

        Livewire::test(KanbanWidget::class, ['projectSlug' => $project->slug])
            ->assertStatus(200)
            ->assertSet('project.id', $project->id);
    }

    public function test_widget_updates_task_order()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
            'order' => 0,
        ]);

        $this->actingAs($user);

        Livewire::test(KanbanWidget::class, ['projectSlug' => $project->slug])
            ->call('updateTaskOrder', [
                ['id' => $task->id, 'status' => 'done', 'order' => 0],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'done',
            'order' => 0,
        ]);
    }
}
```

**Step 2: Run the tests**

Run: `php artisan test --filter=KanbanWidgetTest`

Fix any issues if they arise.

**Step 3: Commit**

```bash
git add tests/Filament/Widgets/KanbanWidgetTest.php
git commit -m "test: add KanbanWidget feature tests"
```

---

## Task 8: Add Styles and Polish

**Files:**
- Modify: `resources/css/filament.css` (or create if not exists)
- Modify: `resources/views/filament/widgets/kanban-board.blade.php`

**Step 1: Add custom CSS**

Add to your CSS file or create `resources/css/kanban.css`:

```css
.kanban-board {
    user-select: none;
}

.kanban-column {
    max-height: calc(100vh - 250px);
    overflow-y: auto;
}

.kanban-task-card {
    transition: all 0.2s ease;
}

.kanban-task-card:hover {
    transform: translateY(-2px);
}

.kanban-task-card.sortable-ghost {
    opacity: 0.5;
    background-color: rgb(243 244 246);
}

.kanban-task-card.sortable-drag {
    cursor: grabbing;
}

.dark .kanban-task-card.sortable-ghost {
    background-color: rgb(55 65 81);
}
```

**Step 2: Import CSS in widget view**

Update `resources/views/filament/widgets/kanban-board.blade.php` to include:

```blade
@vite(['resources/css/kanban.css'])
```

**Step 3: Commit**

```bash
git add resources/css/
git commit -m "style: add Kanban board styles and polish"
```

---

## Task 9: Final Testing and Documentation

**Step 1: Run full test suite**

Run: `php artisan test`

**Step 2: Manual testing checklist**

- [ ] Navigate to global Kanban board page
- [ ] Verify tasks are displayed in correct columns
- [ ] Test drag and drop between columns
- [ ] Test reordering within a column
- [ ] Navigate to project-specific Kanban
- [ ] Verify custom status workflow works
- [ ] Test with projects that have no status_workflow (uses default)
- [ ] Test with projects that have custom status_workflow

**Step 3: Document usage**

Update `README.md` (if exists) with Kanban board usage:

```markdown
## Kanban Board

The application includes a Kanban board for visualizing and managing tasks.

### Global Kanban Board
Navigate to "Kanban Board" in the sidebar to view all tasks across all projects.

### Project Kanban Board
From any project page, click "Kanban Board" to view tasks specific to that project.

### Custom Status Workflows
Projects can define custom status workflows via the `status_workflow` field. If not set, the default workflow is used:
- Backlog
- To Do
- In Progress
- Review
- Done
```

**Step 4: Final commit**

```bash
git add README.md
git commit -m "docs: add Kanban board usage documentation"
```

---

## Summary

This plan creates a fully functional Kanban board with:

1. **TaskStatus Enum** - Type-safe status definitions with labels and colors
2. **Model Scopes** - Efficient queries for Kanban data
3. **KanbanWidget** - Reusable widget for both global and project-specific views
4. **Pages** - Global Kanban page and Project-specific Kanban page
5. **Drag & Drop** - Livewire sortable integration for task management
6. **Tests** - Comprehensive unit and feature tests
7. **Styles** - Polished UI with Tailwind CSS

**Key files created:**
- `app/Enums/TaskStatus.php`
- `app/Filament/Widgets/KanbanWidget.php`
- `app/Filament/Pages/KanbanBoard.php`
- `app/Filament/Pages/ProjectKanban.php`
- `resources/views/filament/widgets/kanban-board.blade.php`
- `resources/views/filament/pages/kanban-board.blade.php`
- `resources/views/filament/pages/project-kanban.blade.php`

**Key files modified:**
- `app/Models/Task.php` - Added scopes
- `app/Models/Project.php` - Added getKanbanStatuses()
- `app/Filament/Resources/ProjectResource.php` - Added Kanban page route
