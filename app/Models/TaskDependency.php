<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependency extends Model
{
    use HasFactory;

    protected $fillable = [
        'blocking_task_id',
        'blocked_task_id',
    ];

    /**
     * The task that blocks another task from starting.
     */
    public function blockingTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'blocking_task_id');
    }

    /**
     * The task that is blocked by another task.
     */
    public function blockedTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'blocked_task_id');
    }

    /**
     * Boot method to add validation for preventing circular dependencies.
     */
    protected static function booted(): void
    {
        static::creating(function (TaskDependency $dependency) {
            // Prevent self-dependency
            if ($dependency->blocking_task_id === $dependency->blocked_task_id) {
                throw new \InvalidArgumentException('A task cannot depend on itself.');
            }

            // Check for circular dependencies
            if (self::wouldCreateCircularDependency(
                $dependency->blocking_task_id,
                $dependency->blocked_task_id
            )) {
                throw new \InvalidArgumentException('This dependency would create a circular reference.');
            }
        });
    }

    /**
     * Check if creating a dependency would cause a circular reference.
     */
    protected static function wouldCreateCircularDependency(int $blockingTaskId, int $blockedTaskId): bool
    {
        // Check if the blocked task already blocks the blocking task (direct circular)
        if (TaskDependency::where('blocking_task_id', $blockedTaskId)
            ->where('blocked_task_id', $blockingTaskId)
            ->exists()) {
            return true;
        }

        // Check for indirect circular dependencies through the chain
        return self::hasCircularChain($blockedTaskId, $blockingTaskId, []);
    }

    /**
     * Recursively check if there's a circular dependency chain.
     */
    protected static function hasCircularChain(int $currentTaskId, int $targetTaskId, array $visited): bool
    {
        // Prevent infinite recursion
        if (in_array($currentTaskId, $visited)) {
            return false;
        }

        $visited[] = $currentTaskId;

        // Get all tasks that the current task blocks
        $blockedTaskIds = TaskDependency::where('blocking_task_id', $currentTaskId)
            ->pluck('blocked_task_id')
            ->toArray();

        foreach ($blockedTaskIds as $blockedId) {
            if ($blockedId === $targetTaskId) {
                return true; // Found circular dependency
            }

            if (self::hasCircularChain($blockedId, $targetTaskId, $visited)) {
                return true;
            }
        }

        return false;
    }
}
