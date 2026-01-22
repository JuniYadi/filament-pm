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

### 1. Clone and Install Dependencies

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed (includes default roles + admin user)
php artisan migrate --seed

# Build frontend assets
npm run build
```

### 2. Admin User Credentials

After running `php artisan migrate --seed`, a default admin user is created:

- **Email**: `admin@filament-pm.test`
- **Password**: `password`

**Important**: Change the password after first login!

### 3. Configure OpenAI (Optional)

For AI-powered semantic document search, add your OpenAI API key to `.env`:

```env
OPENAI_API_KEY=your_actual_api_key_here
```

### 4. Start Development Server

```bash
# Start all services (server, queue, logs, vite)
composer run dev

# Or individually:
php artisan serve
php artisan queue:listen
php artisan pail
npm run dev
```

### 5. Access the Application

- **Admin Panel**: `http://localhost:8000/admin`
- Login with the admin credentials above

### Creating Additional Admin Users

To create more admin users via tinker:

```bash
php artisan tinker

$user = \App\Models\User::create([
    'name' => 'Another Admin',
    'email' => 'another@example.com',
    'password' => bcrypt('secure-password')
]);
$user->assignRole('Product Manager');
```

## Initial Setup Checklist

After installation, complete these steps:

- [ ] Create first admin user (with Product Manager role)
- [ ] Log in to `/admin`
- [ ] Create your first project
- [ ] Add team members to the project
- [ ] Create tasks and assign them
- [ ] (Optional) Configure OpenAI API key for semantic search

## Usage

### Creating Projects

1. Navigate to **Projects** → **Create Project**
2. Fill in project details:
   - Name: e.g., "Website Redesign"
   - Description: Project overview
   - Status Workflow: Customize statuses (default: Backlog, To Do, In Progress, Review, Done)
3. Save the project

### Managing Team Members

1. Open a project
2. Go to **Team Members** relation tab
3. Click **Attach** to add users
4. Assign roles:
   - **Product Manager**: Full access, can delete tasks
   - **Developer**: Can create/update tasks, cannot delete
   - **Viewer**: Read-only access

### Creating Tasks

1. Open a project
2. Go to **Tasks** relation tab
3. Click **Create Task**
4. Fill in:
   - Title: Task name
   - Description: Task details
   - Assigned To: Select team member
   - Status: Select from project workflow
   - Order: For sorting in lists
5. Save

### Adding Comments to Tasks

1. Open a task
2. Go to **Comments** relation tab
3. Add discussion points or updates

### Semantic Document Search

When you create documents with OpenAI configured:

1. Documents are automatically embedded when saved
2. Use the semantic search to find similar documents:

```php
// In your code or tinker:
$document = \App\Models\Document::find(1);
$similar = $document->findSimilar('project requirements', limit: 5);
```

## Roles and Permissions

| Permission | Product Manager | Developer | Viewer |
|-----------|----------------|-----------|--------|
| View Projects | ✓ | ✓ (member only) | ✓ (member only) |
| Create Projects | ✓ | ✗ | ✗ |
| Edit Projects | ✓ (owner) | ✗ | ✗ |
| Delete Projects | ✓ (owner) | ✗ | ✗ |
| View Tasks | ✓ | ✓ (member/assignee) | ✓ (member/assignee) |
| Create Tasks | ✓ | ✓ (member) | ✗ |
| Edit Tasks | ✓ | ✓ (member/creator) | ✗ |
| Delete Tasks | ✓ | ✗ | ✗ |
| View Documents | ✓ (creator/project) | ✓ (project) | ✓ (project) |
| Create/Edit/Delete Documents | ✓ (creator) | ✗ | ✗ |

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Feature/ProjectTest.php
php artisan test tests/Feature/TaskTest.php
php artisan test tests/Feature/DocumentTest.php
```

## Project Structure

```
app/
├── Models/              # Eloquent models
│   ├── Project.php      # Projects with customizable workflows
│   ├── Task.php         # Tasks with status and ordering
│   ├── Document.php     # Documents with AI embeddings
│   ├── Comment.php      # Task discussions
│   └── User.php         # Users with roles and relationships
├── Policies/            # Authorization policies
├── Services/            # Business logic
│   └── EmbeddingService.php  # OpenAI integration
└── Filament/
    ├── Resources/       # Admin resources (modular)
    │   ├── Projects/
    │   │   ├── RelationManagers/  # Members, Tasks
    │   │   ├── Schemas/           # Forms
    │   │   └── Tables/            # List views
    │   ├── Tasks/
    │   └── Users/
    └── Widgets/          # Dashboard widgets
```

## Troubleshooting

### "This action is unauthorized"

- Ensure you have the correct role assigned
- Product Manager has full access
- Check the policy files for specific permission rules

### Documents not being indexed

- Verify `OPENAI_API_KEY` is set in `.env`
- Check OpenAI API quota/billing
- Run `php artisan tinker` and test:
  ```php
  $doc = \App\Models\Document::first();
  $doc->generateEmbedding();
  ```

### Widget shows no data

- Ensure you are a member of at least one project (non-PM users)
- Check that tasks have the correct project_id

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
