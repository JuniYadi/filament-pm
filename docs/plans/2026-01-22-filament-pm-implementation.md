# Filament PM Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build an open-source project management alternative to Confluence, Jira, and Kanban tools with AI-enhanced knowledgebase using Laravel 12 + Filament + Filament Shield.

**Architecture:**
- Role-based access control via Filament Shield (Product Manager, Developer, Viewer)
- Projects contain tasks with customizable statuses
- Freeform documents with optional project/task linking
- Local vector embeddings for semantic search + optional cloud AI (user-provided API keys)

**Tech Stack:**
- Laravel 12, PHP 8.5
- Filament 4.x (admin panel)
- Filament Shield (roles & permissions)
- Spatie Laravel Permission (underlying auth)
- PGVector/pgvector-php (vector embeddings)
- Laravel Scout (search)

---

## Phase 1: Foundation (Packages & Setup)

### Task 1: Install Filament Panel

**Files:**
- Modify: `composer.json`

**Step 1: Install Filament**

```bash
composer require filament/filament:"^4.0"
```

**Step 2: Install Filament Panel**

```bash
php artisan filament:install --panels
```

**Expected output:** Creates `app/Filament/` directory, config files, and AdminPanelProvider

**Step 3: Create admin user**

```bash
php artisan make:filament-user
```

**Expected prompts:** Name, email, password

**Step 4: Commit**

```bash
git add composer.json composer.lock app/Filament/ config/
git commit -m "feat: install Filament 4.x panel"
```

---

### Task 2: Install Filament Shield

**Files:**
- Modify: `composer.json`
- Modify: `app/Models/User.php`
- Create: `config/filament-shield.php` (via publish)

**Step 1: Install Filament Shield**

```bash
composer require bezhansalleh/filament-shield:"^4.0"
```

**Step 2: Publish Shield config**

```bash
php artisan vendor:publish --tag="filament-shield-config"
```

**Step 3: Add HasRoles trait to User**

Read `app/Models/User.php` first, then add the trait:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles; // Add this

class User extends Authenticatable
{
    use HasApiTokens, HasRoles; // Add HasRoles
```

**Step 4: Run Shield setup**

```bash
php artisan shield:setup
```

**Expected output:** Creates roles/permissions tables, publishes migrations

**Step 5: Run migrations**

```bash
php artisan migrate
```

**Step 6: Generate permissions**

```bash
php artisan shield:generate --all
```

**Step 7: Commit**

```bash
git add composer.json composer.lock app/Models/User.php config/ database/migrations/
git commit -m "feat: install and configure Filament Shield for role-based permissions"
```

---

### Task 3: Create Default Roles

**Files:**
- Create: `database/seeders/RoleSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

**Step 1: Create RoleSeeder**

```bash
php artisan make:seeder RoleSeeder
```

**Step 2: Write the seeder**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $productManager = Role::create(['name' => 'Product Manager']);
        $developer = Role::create(['name' => 'Developer']);
        $viewer = Role::create(['name' => 'Viewer']);

        // Product Manager gets all permissions (will be granted via Shield UI)
        // Developer and Viewer will have specific permissions assigned via UI

        $this->command->info('Roles created: Product Manager, Developer, Viewer');
    }
}
```

**Step 3: Register seeder in DatabaseSeeder**

```php
public function run(): void
{
    $this->call([
        RoleSeeder::class,
    ]);
}
```

**Step 4: Run seeder**

```bash
php artisan db:seed --class=RoleSeeder
```

**Expected output:** "Roles created: Product Manager, Developer, Viewer"

**Step 5: Commit**

```bash
git add database/seeders/
git commit -m "feat: create default roles (Product Manager, Developer, Viewer)"
```

---

## Phase 2: Core Domain Models

### Task 4: Create Projects Model & Migration

**Files:**
- Create: `database/migrations/2026_01_22_000001_create_projects_table.php`
- Create: `app/Models/Project.php`
- Create: `database/factories/ProjectFactory.php`
- Create: `database/seeders/ProjectSeeder.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_projects_table
```

**Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('status_workflow')->nullable(); // Customizable workflow per project
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
```

**Step 3: Create model**

```bash
php artisan make:model Project
```

**Step 4: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    protected $casts = [
        'status_workflow' => 'array',
    ];

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
}
```

**Step 5: Run migration**

```bash
php artisan migrate
```

**Step 6: Create factory**

```bash
php artisan make:factory ProjectFactory --model=Project
```

**Step 7: Write factory**

```php
<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => str($name)->slug(),
            'description' => fake()->paragraph(),
            'status_workflow' => null, // Will use default
        ];
    }
}
```

**Step 8: Commit**

```bash
git add database/ app/Models/
git commit -m "feat: create Project model with owner, members, and customizable status workflow"
```

---

### Task 5: Create Project Members Pivot Table

**Files:**
- Create: `database/migrations/2026_01_22_000002_create_project_members_table.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_project_members_table
```

**Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member'); // member, viewer, etc.
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
```

**Step 3: Run migration**

```bash
php artisan migrate
```

**Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: create project_members pivot table for many-to-many relationship"
```

---

### Task 6: Create Tasks Model & Migration

**Files:**
- Create: `database/migrations/2026_01_22_000003_create_tasks_table.php`
- Create: `app/Models/Task.php`
- Create: `database/factories/TaskFactory.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_tasks_table
```

**Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo');
            $table->integer('order')->default(0); // For Kanban ordering
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
```

**Step 3: Create model**

```bash
php artisan make:model Task
```

**Step 4: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'status',
        'order',
    ];

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
}
```

**Step 5: Run migration**

```bash
php artisan migrate
```

**Step 6: Create factory**

```bash
php artisan make:factory TaskFactory --model=Task
```

**Step 7: Write factory**

```php
<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['todo', 'in_progress', 'review', 'done']),
            'order' => fake()->numberBetween(0, 100),
        ];
    }
}
```

**Step 8: Commit**

```bash
git add database/ app/Models/
git commit -m "feat: create Task model with project, assignment, and status tracking"
```

---

### Task 7: Create Comments Model & Migration

**Files:**
- Create: `database/migrations/2026_01_22_000004_create_comments_table.php`
- Create: `app/Models/Comment.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_comments_table
```

**Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
```

**Step 3: Create model**

```bash
php artisan make:model Comment
```

**Step 4: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

**Step 5: Run migration**

```bash
php artisan migrate
```

**Step 6: Commit**

```bash
git add database/ app/Models/
git commit -m "feat: create Comment model for task discussions"
```

---

### Task 8: Create Documents Model & Migration (Knowledgebase)

**Files:**
- Create: `database/migrations/2026_01_22_000005_create_documents_table.php`
- Create: `app/Models/Document.php`
- Create: `database/factories/DocumentFactory.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_documents_table
```

**Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content'); // Markdown content
            $table->json('embedding')->nullable(); // Vector embedding for AI search
            $table->timestamps();

            $table->index('embedding', 'documents_embedding_index'); // For vector search
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
```

**Step 3: Create model**

```bash
php artisan make:model Document
```

**Step 4: Write the model**

```php
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

    protected $casts = [
        'embedding' => 'array',
    ];

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
```

**Step 5: Run migration**

```bash
php artisan migrate
```

**Step 6: Create factory**

```bash
php artisan make:factory DocumentFactory --model=Document
```

**Step 7: Write factory**

```php
<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $title = fake()->words(4, true);

        return [
            'created_by' => User::factory(),
            'project_id' => null,
            'title' => $title,
            'slug' => str($title)->slug(),
            'content' => fake()->paragraphs(5, true),
            'embedding' => null,
        ];
    }
}
```

**Step 8: Commit**

```bash
git add database/ app/Models/
git commit -m "feat: create Document model for freeform knowledgebase with vector embeddings"
```

---

### Task 9: Create Documentable Pivot Table (Polymorphic Relations)

**Files:**
- Create: `database/migrations/2026_01_22_000006_create_documentables_table.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_documentables_table
```

**Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->morphs('documentable'); // entity_type, entity_id
            $table->timestamps();

            $table->index(['document_id', 'documentable_type', 'documentable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentables');
    }
};
```

**Step 3: Run migration**

```bash
php artisan migrate
```

**Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: create documentables polymorphic pivot table for context-aware docs"
```

---

## Phase 3: Filament Resources

### Task 10: Create Project Resource

**Files:**
- Create: `app/Filament/Resources/ProjectResource.php`
- Create: `app/Filament/Resources/ProjectResource/Pages/CreateProject.php`
- Create: `app/Filament/Resources/ProjectResource/Pages/EditProject.php`
- Create: `app/Filament/Resources/ProjectResource/Pages/ListProjects.php`

**Step 1: Generate resource**

```bash
php artisan make:filament-resource Project --generate --soft-deletes
```

**Step 2: Write the ProjectResource**

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->counts('tasks')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
```

**Step 3: Run Pint**

```bash
vendor/bin/pint app/Filament/Resources/ProjectResource.php
```

**Step 4: Commit**

```bash
git add app/Filament/Resources/ProjectResource/
git commit -m "feat: create ProjectResource with form and table"
```

---

### Task 11: Create Task Resource

**Files:**
- Create: `app/Filament/Resources/TaskResource.php`
- Create: `app/Filament/Resources/TaskResource/Pages/` (auto-generated)

**Step 1: Generate resource**

```bash
php artisan make:filament-resource Task --generate
```

**Step 2: Write the TaskResource**

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('assigned_to')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'todo' => 'Todo',
                                'in_progress' => 'In Progress',
                                'review' => 'Review',
                                'done' => 'Done',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('order')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'todo' => 'gray',
                        'in_progress' => 'blue',
                        'review' => 'yellow',
                        'done' => 'green',
                    }),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'todo' => 'Todo',
                        'in_progress' => 'In Progress',
                        'review' => 'Review',
                        'done' => 'Done',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
```

**Step 3: Run Pint**

```bash
vendor/bin/pint app/Filament/Resources/TaskResource.php
```

**Step 4: Commit**

```bash
git add app/Filament/Resources/TaskResource/
git commit -m "feat: create TaskResource with project assignment and status management"
```

---

### Task 12: Create Document Resource

**Files:**
- Create: `app/Filament/Resources/DocumentResource.php`
- Create: `app/Filament/Resources/DocumentResource/Pages/` (auto-generated)

**Step 1: Generate resource**

```bash
php artisan make:filament-resource Document --generate
```

**Step 2: Write the DocumentResource**

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Knowledgebase';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->label('Link to Project (optional)'),

                        Forms\Components\MarkdownEditor::make('content')
                            ->required()
                            ->columnSpanFull()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('attachments'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('linked_to_project')
                    ->query(fn ($query) => $query->whereNotNull('project_id'))
                    ->label('Linked to Project'),

                Tables\Filters\Filter::make('standalone')
                    ->query(fn ($query) => $query->whereNull('project_id'))
                    ->label('Standalone'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
```

**Step 3: Run Pint**

```bash
vendor/bin/pint app/Filament/Resources/DocumentResource.php
```

**Step 4: Commit**

```bash
git add app/Filament/Resources/DocumentResource/
git commit -m "feat: create DocumentResource with markdown editor and project linking"
```

---

## Phase 4: Authorization (Policies)

### Task 13: Create Project Policy

**Files:**
- Create: `app/Policies/ProjectPolicy.php`

**Step 1: Generate policy via Shield**

```bash
php artisan shield:generate --resource=ProjectResource
```

**Expected output:** Creates `app/Policies/ProjectPolicy.php` with standard methods

**Step 2: Modify policy for custom access logic**

Read the generated policy and modify:

```php
<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Everyone can view projects list
    }

    public function view(User $user, Project $project): bool
    {
        // Owner or member can view
        return $project->owner_id === $user->id
            || $project->members()->where('users.id', $user->id)->exists()
            || $user->can('view any project');
    }

    public function create(User $user): bool
    {
        return $user->can('create projects');
    }

    public function update(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $user->can('edit any project');
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $user->can('delete any project');
    }

    public function grantAccess(User $user, Project $project): bool
    {
        // Custom permission for adding members
        return $project->members()->where('users.id', $user->id)->exists()
            && $user->hasAnyPermission(['manage project members', 'edit any project']);
    }
}
```

**Step 3: Add custom permissions to Shield config**

Edit `config/filament-shield.php`:

```php
'custom_permissions' => [
    'GrantAccess:Project' => 'Grant access to project',
    'ViewAny:Project' => 'View any project',
],
```

**Step 4: Regenerate permissions**

```bash
php artisan shield:generate
```

**Step 5: Run Pint**

```bash
vendor/bin/pint app/Policies/ProjectPolicy.php
```

**Step 6: Commit**

```bash
git add app/Policies/ config/
git commit -m "feat: create ProjectPolicy with member-based access control"
```

---

### Task 14: Create Task Policy

**Files:**
- Create: `app/Policies/TaskPolicy.php`

**Step 1: Generate policy**

```bash
php artisan shield:generate --resource=TaskResource
```

**Step 2: Modify policy**

```php
<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        // Can view if member of task's project
        return $task->project->members()->where('users.id', $user->id)->exists()
            || $task->project->owner_id === $user->id
            || $user->can('view any task');
    }

    public function create(User $user): bool
    {
        return $user->can('create tasks');
    }

    public function update(User $user, Task $task): bool
    {
        // Assigned user, creator, project owner, or with permission
        return $task->assigned_to === $user->id
            || $task->created_by === $user->id
            || $task->project->owner_id === $user->id
            || $user->can('edit any task');
    }

    public function delete(User $user, Task $task): bool
    {
        return $task->created_by === $user->id
            || $task->project->owner_id === $user->id
            || $user->can('delete any task');
    }
}
```

**Step 3: Run Pint**

```bash
vendor/bin/pint app/Policies/TaskPolicy.php
```

**Step 4: Commit**

```bash
git add app/Policies/
git commit -m "feat: create TaskPolicy with assignment-based access control"
```

---

### Task 15: Create Document Policy

**Files:**
- Create: `app/Policies/DocumentPolicy.php`

**Step 1: Generate policy**

```bash
php artisan shield:generate --resource=DocumentResource
```

**Step 2: Modify policy**

```php
<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        // Author, project members, or with global permission
        if ($document->created_by === $user->id) {
            return true;
        }

        if ($document->project_id) {
            return $document->project->members()->where('users.id', $user->id)->exists()
                || $document->project->owner_id === $user->id;
        }

        return $user->can('view any document');
    }

    public function create(User $user): bool
    {
        return $user->can('create documents');
    }

    public function update(User $user, Document $document): bool
    {
        return $document->created_by === $user->id
            || $user->can('edit any document');
    }

    public function delete(User $user, Document $document): bool
    {
        return $document->created_by === $user->id
            || $user->can('delete any document');
    }
}
```

**Step 3: Run Pint**

```bash
vendor/bin/pint app/Policies/DocumentPolicy.php
```

**Step 4: Commit**

```bash
git add app/Policies/
git commit -m "feat: create DocumentPolicy with authorship-based access control"
```

---

### Task 16: Create Comment Policy

**Files:**
- Create: `app/Policies/CommentPolicy.php`

**Step 1: Create policy manually** (no resource for comments)

```bash
php artisan make:policy CommentPolicy --model=Comment
```

**Step 2: Write policy**

```php
<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function view(User $user, Comment $comment): bool
    {
        // Can view if can view the associated task
        return $user->can('view', $comment->task);
    }

    public function create(User $user): bool
    {
        return $user->can('create comments');
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id
            || $user->can('edit any comment');
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id
            || $user->can('delete any comment');
    }
}
```

**Step 3: Register policy in AuthServiceProvider**

Edit `app/Providers/AuthServiceProvider.php`:

```php
protected $policies = [
    Comment::class => CommentPolicy::class,
];
```

**Step 4: Run Pint**

```bash
vendor/bin/pint app/Policies/CommentPolicy.php
```

**Step 5: Commit**

```bash
git add app/Policies/ app/Providers/
git commit -m "feat: create CommentPolicy for task discussion access control"
```

---

## Phase 5: AI Vector Embeddings

### Task 17: Install Vector Search Dependencies

**Files:**
- Modify: `composer.json`
- Modify: `config/scout.php`

**Step 1: Install Scout**

```bash
composer require laravel/scout
```

**Step 2: Publish Scout config**

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

**Step 3: Install pgvector PHP driver**

```bash
composer require pgvector/pgvector-php
```

**Step 4: Enable pgvector extension in database**

Edit `.env` to use a database with pgvector enabled, or create a migration to add it:

**Step 5: Create migration for pgvector setup**

```bash
php artisan make:migration setup_pgvector_extension
```

**Step 6: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS vector');
    }
};
```

**Step 7: Run migration**

```bash
php artisan migrate
```

**Step 8: Configure Scout for Documents**

Edit `app/Models/Document.php`, add `Searchable` trait:

```php
use Laravel\Scout\Searchable;

class Document extends Model
{
    use HasFactory, Searchable;
```

**Step 9: Add toSearchableArray method**

```php
public function toSearchableArray(): array
{
    return [
        'title' => $this->title,
        'content' => strip_tags($this->content),
    ];
}
```

**Step 10: Commit**

```bash
git add composer.json composer.lock config/ app/Models/Document.php database/migrations/
git commit -m "feat: install Scout and pgvector for vector search"
```

---

### Task 18: Create AI Embedding Service

**Files:**
- Create: `app/Services/AiEmbeddingService.php`
- Create: `config/ai-embeddings.php`

**Step 1: Create config file**

```php
<?php

return [
    'driver' => env('AI_EMBEDDING_DRIVER', 'local'),

    'drivers' => [
        'local' => [
            // Using simple tf-idf or similar for local-only approach
            'enabled' => true,
        ],

        'openai' => [
            'enabled' => env('OPENAI_API_KEY') !== null,
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        ],

        'anthropic' => [
            'enabled' => env('ANTHROPIC_API_KEY') !== null,
            'api_key' => env('ANTHROPIC_API_KEY'),
        ],
    ],
];
```

**Step 2: Create service**

```bash
php artisan make:class Services/AiEmbeddingService
```

**Step 3: Write service**

```php
<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class AiEmbeddingService
{
    protected string $driver;
    protected array $config;

    public function __construct()
    {
        $this->driver = config('ai-embeddings.driver', 'local');
        $this->config = config("ai-embeddings.drivers.{$this->driver}", []);
    }

    public function generateEmbedding(string $text): ?array
    {
        if ($this->driver === 'openai' && $this->config['enabled'] ?? false) {
            return $this->generateOpenAiEmbedding($text);
        }

        // Fallback: generate simple hash-based embedding for local search
        return $this->generateLocalEmbedding($text);
    }

    protected function generateOpenAiEmbedding(string $text): ?array
    {
        $response = Http::withToken($this->config['api_key'])
            ->asJson()
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->config['model'] ?? 'text-embedding-3-small',
                'input' => substr($text, 0, 8191), // OpenAI limit
            ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json('data.0.embedding');
    }

    protected function generateLocalEmbedding(string $text): array
    {
        // Simple word frequency vector for basic search
        $words = array_filter(str_word_count(strtolower($text), 1));
        $uniqueWords = array_unique($words);

        // Create a 512-dimensional vector based on word hashes
        $vector = array_fill(0, 512, 0);

        foreach ($uniqueWords as $word) {
            $index = crc32($word) % 512;
            $vector[$index] += 1;
        }

        // Normalize
        $magnitude = sqrt(array_sum(array_map(fn ($v) => $v ** 2, $vector)));
        if ($magnitude > 0) {
            $vector = array_map(fn ($v) => $v / $magnitude, $vector);
        }

        return $vector;
    }

    public function similarity(array $embedding1, array $embedding2): float
    {
        if (count($embedding1) !== count($embedding2)) {
            return 0;
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += $embedding1[$i] ** 2;
            $magnitude2 += $embedding2[$i] ** 2;
        }

        $magnitude = sqrt($magnitude1) * sqrt($magnitude2);

        return $magnitude > 0 ? $dotProduct / $magnitude : 0;
    }
}
```

**Step 4: Register config in main config**

Edit `config/app.php` in providers array (if needed for custom config loading).

**Step 5: Run Pint**

```bash
vendor/bin/pint app/Services/AiEmbeddingService.php
```

**Step 6: Commit**

```bash
git add config/ app/Services/
git commit -m "feat: create AI embedding service with local and OpenAI support"
```

---

### Task 19: Integrate Embeddings into Document Model

**Files:**
- Modify: `app/Models/Document.php`

**Step 1: Add embedding generation on save**

```php
use App\Services\AiEmbeddingService;

class Document extends Model
{
    // ... existing code ...

    protected static function booted(): void
    {
        static::saving(function (Document $document) {
            // Generate embedding only if content changed
            if ($document->isDirty('content')) {
                $service = app(AiEmbeddingService::class);
                $document->embedding = $service->generateEmbedding(
                    $document->title . ' ' . strip_tags($document->content)
                );
            }
        });
    }
}
```

**Step 2: Add search scope**

```php
public function scopeSemanticSearch($query, string $searchText, int $limit = 10)
{
    $service = app(AiEmbeddingService::class);
    $searchEmbedding = $service->generateEmbedding($searchText);

    if (!$searchEmbedding) {
        return $query->where('content', 'like', "%{$searchText}%");
    }

    // Get all documents with embeddings
    $documents = self::whereNotNull('embedding')
        ->get()
        ->map(fn ($doc) => [
            'document' => $doc,
            'similarity' => $service->similarity($searchEmbedding, $doc->embedding ?? []),
        ])
        ->sortByDesc('similarity')
        ->take($limit)
        ->pluck('document');

    return $query->whereIn('id', $documents->pluck('id'));
}
```

**Step 3: Run Pint**

```bash
vendor/bin/pint app/Models/Document.php
```

**Step 4: Commit**

```bash
git add app/Models/Document.php
git commit -m "feat: integrate AI embeddings into Document model with semantic search"
```

---

## Phase 6: Testing

### Task 20: Create Feature Tests for Projects

**Files:**
- Create: `tests/Feature/ProjectResourceTest.php`

**Step 1: Create test**

```bash
php artisan make:test ProjectResourceTest --phpunit
```

**Step 2: Write tests**

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/admin/projects', [
                'name' => 'Test Project',
                'slug' => 'test-project',
                'description' => 'A test project',
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'owner_id' => $user->id,
        ]);
    }

    public function test_owner_can_view_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get("/admin/projects/{$project->id}");

        $response->assertStatus(200);
    }

    public function test_owner_can_delete_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user)
            ->delete("/admin/projects/{$project->id}");

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }
}
```

**Step 3: Run test**

```bash
php artisan test --filter=test_user_can_create_project
```

**Step 4: Commit**

```bash
git add tests/
git commit -m "test: add ProjectResource feature tests"
```

---

### Task 21: Create Feature Tests for Tasks

**Files:**
- Create: `tests/Feature/TaskResourceTest.php`

**Step 1: Create test**

```bash
php artisan make:test TaskResourceTest --phpunit
```

**Step 2: Write tests**

```php
<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post('/admin/tasks', [
                'title' => 'Test Task',
                'project_id' => $project->id,
                'status' => 'todo',
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'project_id' => $project->id,
        ]);
    }

    public function test_assigned_user_can_update_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->put("/admin/tasks/{$task->id}", [
                'title' => 'Updated Task',
                'project_id' => $project->id,
                'status' => 'done',
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'done',
        ]);
    }
}
```

**Step 3: Run tests**

```bash
php artisan test tests/Feature/TaskResourceTest.php
```

**Step 4: Commit**

```bash
git add tests/
git commit -m "test: add TaskResource feature tests"
```

---

### Task 22: Create Tests for Semantic Search

**Files:**
- Create: `tests/Feature/SemanticSearchTest.php`

**Step 1: Create test**

```bash
php artisan make:test SemanticSearchTest --phpunit
```

**Step 2: Write tests**

```php
<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\AiEmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemanticSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_generates_embedding_on_save(): void
    {
        $user = User::factory()->create();

        $document = Document::create([
            'created_by' => $user->id,
            'title' => 'Test Document',
            'slug' => 'test-document',
            'content' => 'This is a test document about semantic search.',
        ]);

        $this->assertNotNull($document->embedding);
        $this->assertIsArray($document->embedding);
    }

    public function test_semantic_search_finds_relevant_documents(): void
    {
        $user = User::factory()->create();

        Document::create([
            'created_by' => $user->id,
            'title' => 'Laravel Guide',
            'slug' => 'laravel-guide',
            'content' => 'Laravel is a PHP framework for web development.',
        ]);

        Document::create([
            'created_by' => $user->id,
            'title' => 'Cooking Recipes',
            'slug' => 'cooking-recipes',
            'content' => 'How to make pizza and pasta at home.',
        ]);

        $results = Document::semanticSearch('PHP programming')->get();

        $this->assertTrue(
            $results->contains('title', 'Laravel Guide')
        );
    }
}
```

**Step 3: Run tests**

```bash
php artisan test tests/Feature/SemanticSearchTest.php
```

**Step 4: Commit**

```bash
git add tests/
git commit -m "test: add semantic search feature tests"
```

---

## Phase 7: Final Polish

### Task 23: Add Role Select to User Resource

**Files:**
- Modify: `app/Filament/Resources/UserResource.php`

**Step 1: Read existing UserResource**

```bash
cat app/Filament/Resources/UserResource.php
```

**Step 2: Add roles field to form**

Add this to the form schema:

```php
Forms\Components\Section::make('Roles')
    ->schema([
        Forms\Components\Select::make('roles')
            ->relationship('roles', 'name')
            ->multiple()
            ->preload()
            ->searchable(),
    ]),
```

**Step 3: Run Pint**

```bash
vendor/bin/pint app/Filament/Resources/UserResource.php
```

**Step 4: Commit**

```bash
git add app/Filament/Resources/UserResource.php
git commit -m "feat: add role selection to UserResource"
```

---

### Task 24: Add Members Relation Manager to Project Resource

**Files:**
- Modify: `app/Filament/Resources/ProjectResource.php`
- Create: `app/Filament/Resources/ProjectResource/RelationManagers/MembersRelationManager.php`

**Step 1: Create relation manager**

```bash
php artisan make:filament-relation-manager ProjectResource members user
```

**Step 2: Write relation manager**

```php
<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role')
                    ->options([
                        'member' => 'Member',
                        'viewer' => 'Viewer',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('pivot.role')->label('Project Role'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
```

**Step 3: Add to ProjectResource**

Add to `getRelations()`:

```php
public static function getRelations(): array
{
    return [
        RelationManagers\MembersRelationManager::class,
    ];
}
```

**Step 4: Run Pint**

```bash
vendor/bin/pint app/Filament/Resources/ProjectResource/RelationManagers/MembersRelationManager.php
```

**Step 5: Commit**

```bash
git add app/Filament/Resources/ProjectResource/
git commit -m "feat: add members relation manager to ProjectResource"
```

---

### Task 25: Add Tasks Relation Manager to Project Resource

**Files:**
- Create: `app/Filament/Resources/ProjectResource/RelationManagers/TasksRelationManager.php`

**Step 1: Create relation manager**

```bash
php artisan make:filament-relation-manager ProjectResource tasks task
```

**Step 2: Write relation manager**

```php
<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Select::make('status')
                    ->options([
                        'todo' => 'Todo',
                        'in_progress' => 'In Progress',
                        'review' => 'Review',
                        'done' => 'Done',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'todo' => 'gray',
                        'in_progress' => 'blue',
                        'review' => 'yellow',
                        'done' => 'green',
                    }),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

**Step 3: Add to ProjectResource**

Update `getRelations()`:

```php
public static function getRelations(): array
{
    return [
        RelationManagers\MembersRelationManager::class,
        RelationManagers\TasksRelationManager::class,
    ];
}
```

**Step 4: Run Pint**

```bash
vendor/bin/pint app/Filament/Resources/ProjectResource/RelationManagers/TasksRelationManager.php
```

**Step 5: Commit**

```bash
git add app/Filament/Resources/ProjectResource/
git commit -m "feat: add tasks relation manager to ProjectResource"
```

---

### Task 26: Add Comments Relation Manager to Task Resource

**Files:**
- Create: `app/Filament/Resources/TaskResource/RelationManagers/CommentsRelationManager.php`

**Step 1: Create relation manager**

```bash
php artisan make:filament-relation-manager TaskResource comments comment
```

**Step 2: Write relation manager**

```php
<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('content')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

**Step 3: Add to TaskResource**

Update `getRelations()`:

```php
public static function getRelations(): array
{
    return [
        RelationManagers\CommentsRelationManager::class,
    ];
}
```

**Step 4: Run Pint**

```bash
vendor/bin/pint app/Filament/Resources/TaskResource/RelationManagers/CommentsRelationManager.php
```

**Step 5: Commit**

```bash
git add app/Filament/Resources/TaskResource/
git commit -m "feat: add comments relation manager to TaskResource"
```

---

### Task 27: Create Kanban Widget

**Files:**
- Create: `app/Filament/Widgets/TasksKanbanWidget.php`

**Step 1: Create widget**

```bash
php artisan make:filament-widget TasksKanbanWidget
```

**Step 2: Write widget**

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TasksKanbanWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->where('assigned_to', auth()->id())
                    ->with(['project', 'assignedTo'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->description(fn (Task $record): string => $record->project->name),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'todo' => 'gray',
                        'in_progress' => 'blue',
                        'review' => 'yellow',
                        'done' => 'green',
                    }),

                Tables\Columns\TextColumn::make('project.name')
                    ->badge()
                    ->color('info'),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order');
    }
}
```

**Step 3: Run Pint**

```bash
vendor/bin/pint app/Filament/Widgets/TasksKanbanWidget.php
```

**Step 4: Commit**

```bash
git add app/Filament/Widgets/
git commit -m "feat: create tasks kanban widget for dashboard"
```

---

### Task 28: Run Pint on All Files

**Files:**
- All PHP files in the project

**Step 1: Run Pint on dirty files only**

```bash
vendor/bin/pint --dirty
```

**Expected output:** Lists files that were formatted

**Step 2: Check if any files were modified**

```bash
git status
```

**Step 3: Commit any formatting changes**

```bash
git add .
git commit -m "style: run Laravel Pint on all files"
```

---

### Task 29: Run Full Test Suite

**Files:**
- All test files

**Step 1: Run all tests**

```bash
php artisan test --compact
```

**Expected output:** All tests passing

**Step 2: Fix any failing tests**

If tests fail, read the error output and fix accordingly.

**Step 3: Re-run tests**

```bash
php artisan test --compact
```

**Step 4: Commit**

```bash
git add .
git commit -m "test: ensure all tests pass"
```

---

### Task 30: Update README

**Files:**
- Modify: `README.md`

**Step 1: Update README**

```bash
cat > README.md << 'EOF'
# Filament PM

An open-source project management alternative to Confluence, Jira, and Kanban tools with AI-enhanced knowledgebase.

## Features

- **Project Management**: Create projects, manage tasks with customizable statuses
- **Role-Based Access Control**: Product Manager, Developer, and Viewer roles via Filament Shield
- **Knowledgebase**: Freeform documentation with markdown support
- **AI-Enhanced Search**: Local vector embeddings for semantic search (no API key required)
- **Optional Cloud AI**: Bring your own OpenAI/Anthropic API key for advanced features
- **Context-Aware**: Link documents to projects and tasks for context

## Tech Stack

- Laravel 12
- Filament 4.x (admin panel)
- Filament Shield (roles & permissions)
- Spatie Laravel Permission
- Laravel Scout + pgvector (vector search)

## Installation

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure
4. Run `php artisan key:generate`
5. Run `php artisan migrate`
6. Run `php artisan shield:setup`
7. Run `php artisan db:seed --class=RoleSeeder`
8. Run `php artisan make:filament-user` to create admin
9. Run `npm install && npm run build`

## Usage

### Roles

- **Product Manager**: Full access to projects, tasks, and documents
- **Developer**: Create tasks, update assignments, grant read access
- **Viewer**: Read-only access to assigned content

### AI Features

Local semantic search works out of the box. For advanced features:

```bash
# .env
OPENAI_API_KEY=your-key-here
# or
ANTHROPIC_API_KEY=your-key-here
```

## Development

```bash
# Run tests
php artisan test

# Format code
vendor/bin/pint

# Run dev server
composer run dev
```

## License

MIT
EOF
```

**Step 2: Commit**

```bash
git add README.md
git commit -m "docs: update README with project information"
```

---

## Final Steps

### Task 31: Tag Release

**Step 1: Create git tag**

```bash
git tag -a v0.1.0 -m "Initial release of Filament PM"
```

**Step 2: Push to remote**

```bash
git push origin main --tags
```

**Step 3: Celebrate** 🎉

---

## Summary

This implementation plan creates a fully functional project management system with:

1. ✅ **Filament 4.x** admin panel
2. ✅ **Filament Shield** for role-based permissions
3. ✅ **Projects** with customizable status workflows
4. ✅ **Tasks** with assignment and Kanban support
5. ✅ **Documents** (knowledgebase) with markdown
6. ✅ **Comments** for task discussions
7. ✅ **AI vector embeddings** for semantic search
8. ✅ **Policies** for authorization
9. ✅ **Tests** for core functionality
10. ✅ **Relation managers** for nested resources

**Total estimated tasks: 31**
**Estimated commits: ~31 (one per task as per TDD)**

---

## Notes for Implementation

1. **Always create a new branch** before starting implementation
2. **Commit each file/task separately** as outlined
3. **Run tests frequently** - after each test file is created
4. **Use TDD**: Write failing test first, then implement
5. **Run Pint** before each commit to maintain code style
6. **Use the superpowers:executing-plans skill** to execute this plan
