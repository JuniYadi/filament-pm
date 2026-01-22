# Document Version Tracking Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add version tracking to the Document model using mansoor/filament-versionable package to track and restore changes.

**Architecture:**
1. Install the mansoor/filament-versionable package which depends on overtrue/laravel-versionable
2. Add the Versionable trait to the Document model with SNAPSHOT strategy for reliable tracking
3. Create a DocumentRevisions page extending RevisionsPage
4. Add RevisionsAction to EditDocument page
5. Update the theme CSS to include the plugin's styles
6. Run migrations to create the versions table

**Tech Stack:** mansoor/filament-versionable, overtrue/laravel-versionable, Filament v4, Laravel 12

---

### Task 1: Install the versionable package

**Files:**
- Modify: `composer.json` (via composer require)

**Step 1: Install the package via composer**

Run: `composer require mansoor/filament-versionable --no-interaction`
Expected: Package installed successfully

**Step 2: Publish config and migrations**

Run: `php artisan vendor:publish --provider="Overtrue\LaravelVersionable\ServiceProvider" --no-interaction`
Expected: Config file published to config/versionable.php

**Step 3: Run migrations to create versions table**

Run: `php artisan migrate --no-interaction`
Expected: migrations table created successfully

**Step 4: Commit**

```bash
git add composer.json composer.lock config/versionable.php database/migrations/*
git commit -m "feat: install mansoor/filament-versionable package"
```

---

### Task 2: Add Versionable trait to Document model

**Files:**
- Modify: `app/Models/Document.php:1-120`

**Step 1: Read the Document model**

Run: `cat app/Models/Document.php`
Expected: See current model contents

**Step 2: Add Versionable trait and configure versionable attributes**

```php
<?php

namespace App\Models;

use App\Services\EmbeddingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App;
use Overtrue\LaravelVersionable\VersionStrategy;
use Overtrue\LaravelVersionable\Versionable;

class Document extends Model
{
    use HasFactory, Versionable;

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
```

**Step 3: Run pint to format code**

Run: `vendor/bin/pint --dirty`
Expected: Code formatted correctly

**Step 4: Commit**

```bash
git add app/Models/Document.php
git commit -m "feat: add Versionable trait to Document model"
```

---

### Task 3: Create DocumentRevisions page

**Files:**
- Create: `app/Filament/Resources/Documents/Pages/DocumentRevisions.php`

**Step 1: Create the DocumentRevisions page**

```php
<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Mansoor\FilamentVersionable\RevisionsPage;

/**
 * Revisions page for Document model.
 * Displays all version history with diff viewer and restore capability.
 */
class DocumentRevisions extends RevisionsPage
{
    /**
     * The resource class this page belongs to.
     */
    protected static string $resource = DocumentResource::class;

    /**
     * Strip HTML tags from diff for cleaner display.
     * Since content is Markdown, this makes the diff more readable.
     */
    public function shouldStripTags(): bool
    {
        return true;
    }
}
```

**Step 2: Run pint to format code**

Run: `vendor/bin/pint --dirty`
Expected: Code formatted correctly

**Step 3: Commit**

```bash
git add app/Filament/Resources/Documents/Pages/DocumentRevisions.php
git commit -m "feat: create DocumentRevisions page"
```

---

### Task 4: Add revisions route to DocumentResource

**Files:**
- Modify: `app/Filament/Resources/Documents/DocumentResource.php:42-49`

**Step 1: Update the getPages method**

```php
<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\DocumentRevisions;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
            'revisions' => DocumentRevisions::route('/{record}/revisions'),
        ];
    }
}
```

**Step 2: Run pint to format code**

Run: `vendor/bin/pint --dirty`
Expected: Code formatted correctly

**Step 3: Commit**

```bash
git add app/Filament/Resources/Documents/DocumentResource.php
git commit -m "feat: add revisions route to DocumentResource"
```

---

### Task 5: Add RevisionsAction to EditDocument page

**Files:**
- Modify: `app/Filament/Resources/Documents/Pages/EditDocument.php:1-19`

**Step 1: Add RevisionsAction to header actions**

```php
<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Mansoor\FilamentVersionable\Page\RevisionsAction;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RevisionsAction::make(),
            DeleteAction::make(),
        ];
    }
}
```

**Step 2: Run pint to format code**

Run: `vendor/bin/pint --dirty`
Expected: Code formatted correctly

**Step 3: Commit**

```bash
git add app/Filament/Resources/Documents/Pages/EditDocument.php
git commit -m "feat: add RevisionsAction to EditDocument page"
```

---

### Task 6: Add RevisionsAction to DocumentsTable

**Files:**
- Modify: `app/Filament/Resources/Documents/Tables/DocumentsTable.php`

**Step 1: Read the current DocumentsTable file**

Run: `cat app/Filament/Resources/Documents/Tables/DocumentsTable.php`
Expected: See current table configuration

**Step 2: Add RevisionsAction to table actions (after reading file structure)**

```php
<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Models\Document;
use Filament\Tables\Actions\DeleteAction as TablesDeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Mansoor\FilamentVersionable\Table\RevisionsAction;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->actions([
                EditAction::make(),
                RevisionsAction::make(),
                TablesDeleteAction::make(),
            ]);
    }
}
```

**Step 3: Run pint to format code**

Run: `vendor/bin/pint --dirty`
Expected: Code formatted correctly

**Step 4: Commit**

```bash
git add app/Filament/Resources/Documents/Tables/DocumentsTable.php
git commit -m "feat: add RevisionsAction to DocumentsTable"
```

---

### Task 7: Update theme CSS to include plugin styles

**Files:**
- Modify: `resources/css/filament/admin/theme.css:1-36`

**Step 1: Add versionable plugin imports to theme**

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';
@import '../../../../vendor/mansoor/filament-versionable/resources/css/plugin.css';

@source '../../../../app/Filament/**/*';
@source '../../../../resources/views/filament/**/*';
@source '../../../../vendor/mansoor/filament-versionable/resources/**/*.blade.php';

/* Kanban Board Styles */
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

**Step 2: Build frontend assets**

Run: `npm run build`
Expected: CSS compiled successfully

**Step 3: Commit**

```bash
git add resources/css/filament/admin/theme.css
git commit -m "feat: add versionable plugin styles to theme"
```

---

### Task 8: Test version tracking functionality

**Files:**
- Test: `tests/Feature/DocumentVersioningTest.php`

**Step 1: Create feature test for version tracking**

```php
<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_creates_version_on_update(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create([
            'title' => 'Original Title',
            'content' => 'Original Content',
            'created_by' => $user->id,
        ]);

        // Update document to create a version
        $document->update([
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ]);

        // Assert version was created
        $this->assertCount(1, $document->versions);
        $this->assertEquals('Original Title', $document->versions->first()->contents['title']);
        $this->assertEquals('Original Content', $document->versions->first()->contents['content']);
    }

    public function test_document_can_be_restored_to_previous_version(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'created_by' => $user->id,
        ]);

        $document->update([
            'title' => 'Version 2',
            'content' => 'Content 2',
        ]);

        $firstVersion = $document->versions->first();

        // Restore to first version
        $document->restoreVersion($firstVersion->id);

        // Assert document was restored
        $document->refresh();
        $this->assertEquals('Version 1', $document->title);
        $this->assertEquals('Content 1', $document->content);
    }

    public function test_document_slug_is_not_versioned(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create([
            'title' => 'Test Title',
            'slug' => 'test-title',
            'content' => 'Test Content',
            'created_by' => $user->id,
        ]);

        $document->update(['content' => 'Updated Content']);

        // Slug should not be in versioned attributes
        $versionContents = $document->versions->first()->contents;
        $this->assertArrayNotHasKey('slug', $versionContents);
        $this->assertArrayHasKey('title', $versionContents);
        $this->assertArrayHasKey('content', $versionContents);
    }
}
```

**Step 2: Run the test**

Run: `php artisan test --compact --filter=DocumentVersioningTest`
Expected: All tests pass

**Step 3: Commit**

```bash
git add tests/Feature/DocumentVersioningTest.php
git commit -m "test: add version tracking tests for Document model"
```

---

### Task 9: Verify the implementation works end-to-end

**Files:**
- No files created/modified

**Step 1: Check migrations ran successfully**

Run: `php artisan migrate:status`
Expected: All migrations including versions table are present

**Step 2: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass including existing tests

**Step 3: Final commit if needed**

```bash
# If any adjustments were needed
git add .
git commit -m "fix: address version tracking implementation issues"
```

---

## Summary

After completing these tasks, the Document model will:
1. Automatically create versions whenever title or content changes
2. Display a "Revisions" button in the EditDocument page header
3. Display a "Revisions" action in the Documents table
4. Provide a revisions page showing diff of all changes
5. Allow restoring to any previous version

**Key Implementation Notes:**
- SNAPSHOT strategy is used instead of DIFF for reliability (known bug reports with DIFF)
- HTML tags are stripped from diffs for cleaner Markdown display
- Only title and content are versioned (not slug, embedding, or foreign keys)
- The RevisionsAction only appears when versions exist for a document
