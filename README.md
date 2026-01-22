# Filament PM

A project management system built with Laravel 12, Filament 4, and AI-powered semantic search.

## Features

- **Project Management**
  - Create and manage projects with customizable status workflows
  - Add team members with role-based access (Product Manager, Developer, Viewer)
  - Track tasks with Kanban-style status management

- **Task Management**
  - Create tasks within projects
  - Assign tasks to team members
  - Track task status (Backlog, To Do, In Progress, Review, Done)
  - Add comments for collaboration

- **Document Management**
  - Create standalone or project-linked documents
  - Markdown support for rich content
  - AI-powered semantic search using OpenAI embeddings

- **Role-Based Access Control**
  - Powered by Filament Shield and Spatie Laravel Permission
  - Granular permissions for Projects, Tasks, Documents, and Comments
  - Three default roles: Product Manager, Developer, Viewer

## Tech Stack

- **PHP**: 8.5.0
- **Laravel**: 12.48.1
- **Filament**: 4.5.3 (New modular structure with separate Schema/Table classes)
- **Filament Shield**: 4.1.0 (Role-based permissions)
- **Database**: SQLite (configurable)
- **AI**: OpenAI text-embedding-3-small for semantic search

## Installation

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up OpenAI API key (optional, for semantic search)
# Add to .env: OPENAI_API_KEY=your_key_here

# Run migrations
php artisan migrate

# Seed default roles
php artisan db:seed --class=RoleSeeder

# Build frontend assets
npm run build

# Run development server
composer run dev
```

## Usage

1. **Access the admin panel** at `/admin`
2. **Create a project** with a customizable status workflow
3. **Add team members** to projects with specific roles
4. **Create tasks** and assign them to team members
5. **Create documents** - embeddings are generated automatically if OpenAI API key is configured

## Semantic Search

Documents can be searched semantically using AI embeddings:

```php
// Find similar documents to a query
$similarDocuments = $document->findSimilar('search query', limit: 5);
```

This uses cosine similarity on OpenAI embeddings to find conceptually similar documents.

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Feature/ProjectTest.php
```

## Project Structure

```
app/
├── Models/           # Eloquent models (Project, Task, Document, Comment)
├── Policies/         # Authorization policies
├── Services/         # EmbeddingService for AI features
└── Filament/
    ├── Resources/    # Filament resources (modular structure)
    │   ├── Projects/
    │   │   ├── RelationManagers/
    │   │   ├── Schemas/
    │   │   └── Tables/
    │   ├── Tasks/
    │   └── Users/
    └── Widgets/      # Dashboard widgets
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
