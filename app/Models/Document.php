<?php

namespace App\Models;

use App\Services\EmbeddingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App;
use Overtrue\LaravelVersionable\Versionable;
use Overtrue\LaravelVersionable\VersionStrategy;
use Spatie\Tags\HasTags;

class Document extends Model
{
    use HasFactory, HasTags, Versionable;

    protected $fillable = [
        'created_by',
        'project_id',
        'title',
        'slug',
        'content',
        'embedding',
    ];

    /**
     * Attributes to track for versioning.
     * We track title and content since these are the main editable fields.
     */
    protected array $versionable = [
        'title',
        'content',
    ];

    /**
     * Use SNAPSHOT strategy for reliable version tracking.
     * DIFF strategy has known bug reports.
     */
    protected $versionStrategy = VersionStrategy::SNAPSHOT;

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

    public function tasks(): MorphToMany
    {
        return $this->morphedByMany(
            related: Task::class,
            name: 'documentable',
            table: 'documentables',
        );
    }

    public function projects(): MorphToMany
    {
        return $this->morphedByMany(
            related: Project::class,
            name: 'documentable',
            table: 'documentables',
        );
    }

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

    public function findSimilar(string $query, int $limit = 5): Collection
    {
        /** @var EmbeddingService $service */
        $service = App::make(EmbeddingService::class);

        $queryEmbedding = $service->generateEmbedding($query);

        if (empty($queryEmbedding)) {
            return $this->newQuery()->limit($limit)->get();
        }

        $documents = self::query()
            ->whereNotNull('embedding')
            ->where('id', '!=', $this->id)
            ->get();

        $scored = $documents->mapWithKeys(function (Document $document) use ($service, $queryEmbedding) {
            $similarity = $document->embedding
                ? $service->cosineSimilarity($queryEmbedding, $document->embedding)
                : 0;

            return [$document->id => [
                'document' => $document,
                'similarity' => $similarity,
            ]];
        });

        $results = collect($scored)
            ->sortByDesc('similarity')
            ->take($limit)
            ->map(fn (array $item) => $item['document'])
            ->values()
            ->all();

        return new Collection($results);
    }
}
