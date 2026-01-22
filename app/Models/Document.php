<?php

namespace App\Models;

use App\Services\EmbeddingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App;

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

    protected static function booted(): void
    {
        static::saved(function (Document $document) {
            if ($document->isDirty('content') || $document->isDirty('title')) {
                $document->generateEmbedding();
            }
        });
    }

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

    /**
     * Generate and store embedding for this document.
     */
    public function generateEmbedding(): void
    {
        /** @var EmbeddingService $service */
        $service = App::make(EmbeddingService::class);

        $text = $this->title.' '.$this->content;
        $embedding = $service->generateEmbedding($text);

        if (! empty($embedding)) {
            $this->updateQuietly(['embedding' => $embedding]);
        }
    }

    /**
     * Find similar documents using cosine similarity.
     *
     * @param string $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findSimilar(string $query, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        /** @var EmbeddingService $service */
        $service = App::make(EmbeddingService::class);

        $queryEmbedding = $service->generateEmbedding($query);

        if (empty($queryEmbedding)) {
            return $this->newQuery()->limit($limit)->get();
        }

        return self::query()
            ->whereNotNull('embedding')
            ->where('id', '!=', $this->id)
            ->get()
            ->mapWithKeys(function (Document $document) use ($service, $queryEmbedding) {
                $similarity = $document->embedding
                    ? $service->cosineSimilarity($queryEmbedding, $document->embedding)
                    : 0;

                return [$document->id => [
                    'document' => $document,
                    'similarity' => $similarity,
                ];
            })
            ->sortByDesc('similarity')
            ->take($limit)
            ->map(fn (array $item) => $item['document']);
    }
}
