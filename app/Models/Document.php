<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'project_id',
        'title',
        'slug',
        'content',
        'embedding',
    ];

    public function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Allow documents to be linked to any entity (Task, Project, etc.)
    public function linkedEntities(): MorphToMany
    {
        return $this->morphedByMany(
            related: '*',
            name: 'documentable',
            table: 'documentables',
        );
    }
}
