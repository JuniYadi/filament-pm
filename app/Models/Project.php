<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'status_workflow',
    ];

    public function casts(): array
    {
        return [
            'status_workflow' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

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
}
