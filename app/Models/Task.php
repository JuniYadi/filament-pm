<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Tags\HasTags;

class Task extends Model
{
    use HasFactory, HasTags;

    protected $fillable = [
        'project_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'order',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'priority' => \App\Enums\TaskPriority::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest('created_at');
    }

    public function documents(): MorphToMany
    {
        return $this->morphToMany(
            related: Document::class,
            name: 'documentable',
            table: 'documentables',
        );
    }

    /**
     * Tasks that this task blocks (must complete before this task can start).
     */
    public function blockingTasks(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'blocked_task_id',
            'blocking_task_id'
        )->withTimestamps();
    }

    /**
     * Tasks that block this task (must complete before this task can start).
     */
    public function blockedByTasks(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'blocking_task_id',
            'blocked_task_id'
        )->withTimestamps();
    }

    /**
     * Check if this task is blocked by any incomplete tasks.
     */
    public function isBlocked(): bool
    {
        return $this->blockedByTasks()
            ->where('status', '!=', 'done')
            ->exists();
    }

    /**
     * Get count of blocking tasks that are not yet completed.
     */
    public function getBlockingCountAttribute(): int
    {
        return $this->blockedByTasks()
            ->where('status', '!=', 'done')
            ->count();
    }

    /**
     * Accessor for title with project name.
     */
    public function getTitleWithProjectAttribute(): string
    {
        return $this->title . ($this->project ? ' (' . $this->project->name . ')' : '');
    }

    /**
     * Scope a query to get tasks for Kanban board of a specific project.
     */
    public function scopeForKanban(Builder $query, Project $project): Builder
    {
        return $query->where('project_id', $project->id)
            ->orderByRaw("CASE status
                WHEN 'backlog' THEN 1
                WHEN 'todo' THEN 2
                WHEN 'in_progress' THEN 3
                WHEN 'review' THEN 4
                WHEN 'done' THEN 5
                ELSE 6
            END")
            ->orderBy('order');
    }

    /**
     * Scope a query to get tasks for global Kanban board.
     */
    public function scopeForGlobalKanban(Builder $query): Builder
    {
        return $query->with(['project', 'assignedTo'])
            ->orderByRaw("CASE status
                WHEN 'backlog' THEN 1
                WHEN 'todo' THEN 2
                WHEN 'in_progress' THEN 3
                WHEN 'review' THEN 4
                WHEN 'done' THEN 5
                ELSE 6
            END")
            ->orderBy('order');
    }
}
