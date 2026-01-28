# Filament PM

A self-hosted project management and documentation platform built as a lightweight, powerful alternative to Jira and Confluence.

## Why Filament PM?

**Filament PM** gives you full control over your team's work and knowledge - no per-user pricing, no vendor lock-in, no AI training on your private data.

- 🏠 **Self-Hosted** - Your data, your infrastructure
- 🔍 **AI-Powered Search** - Semantic search built-in (no expensive addons)
- 🎨 **Custom Workflows** - Per-project status customization
- 📊 **Kanban Boards** - Global and project-level views
- 💰 **Zero Licensing Fees** - Open source, forever

## Features

- **Project Management**
  - Create and manage projects with customizable status workflows
  - Add team members with role-based access (Product Manager, Developer, Viewer)
  - Project invitation system with email links and expiration
  - Track tasks with Kanban-style status management

- **Task Management**
  - Create tasks within projects
  - Assign tasks to team members
  - Track task status (Backlog, To Do, In Progress, Review, Done)
  - Add comments for collaboration
  - Activity logging (status changes, assignments, deletions)
  - Bulk operations (change status, reassign, delete)

- **Document Management**
  - Create standalone or project-linked documents
  - Markdown support for rich content
  - AI-powered semantic search using OpenAI embeddings
  - Document versioning with restore capability

- **Authentication & Security**
  - Socialite integration for social login (Google, GitHub, etc.)
  - Powered by Filament Shield and Spatie Laravel Permission
  - Granular permissions for Projects, Tasks, Documents, and Comments
  - Three default roles: Product Manager, Developer, Viewer

## Comparison with Jira & Confluence

### Project & Task Management

| Feature | Jira | Confluence | Filament PM | Notes |
|---------|------|------------|-------------|-------|
| **Projects** | ✅ Full project management | ❌ N/A | ✅ Projects with owner, members, roles | Filament PM: Role-based members (Product Manager, Developer, Viewer) |
| **Tasks** | ✅ With subtasks, epic linking | 🔶 Task macros only | ✅ Tasks with title, description, status | Jira: More hierarchy (Epic → Task → Subtask) |
| **Status Workflow** | 🔶 Custom workflows (Enterprise+ only) | ❌ N/A | ✅ Per-project custom status arrays | **Filament PM advantage**: Each project can define its own workflow |
| **Task Statuses** | ✅ Fully customizable | ❌ N/A | ✅ 5 default statuses (Backlog → Done) | Filament PM: Can be overridden per project |
| **Assignees** | ✅ Multiple assignees | ❌ N/A | ✅ Single assignee | Planned: Multiple assignees per task |
| **Sprints** | ✅ Full Scrum support | ❌ N/A | ❌ Not implemented | Roadmap: Sprint planning |
| **Epics** | ✅ Epic grouping | ❌ N/A | ❌ Not implemented | Roadmap: Task grouping/tags |
| **Time Tracking** | ✅ Native time tracking | ❌ N/A | ❌ Not implemented | Roadmap: Basic time logging |
| **Task Dependencies** | ✅ Blocking/linked issues | ❌ N/A | ❌ Not implemented | Roadmap: Task dependencies |
| **Kanban Board** | ✅ Per-project Kanban | ❌ N/A | ✅ Per-project + Global Kanban | **Filament PM advantage**: Global view across all projects |
| **Task Search** | ✅ Advanced JQL | ✅ Basic search | ✅ Database query | Roadmap: Enhanced search with filters |
| **Task Priorities** | ✅ Customizable priorities | ❌ N/A | 🔶 Enum exists, UI integration planned | See issue #69 for progress |
| **Labels/Tags** | ✅ Full label system | ✅ Labels | ❌ Not implemented | Roadmap: Tag system (see issue #71) |
| **Task Comments** | ✅ Comments + mentions | ✅ Comments | ✅ Comments via RelationManager | Planned: Direct comments on task view |
| **Attachments** | ✅ File attachments | ✅ Attachments | 🔶 Via documents | Can link documents to tasks |
| **Bulk Operations** | ✅ Bulk edit/move | ✅ Bulk operations | ✅ Bulk status, reassign, delete | **Already implemented!** |
| **Task History** | ✅ Full audit trail | ✅ Page history | ✅ Activity log (status, assignee, deletion) | **Already implemented!** |

### Collaboration & Documentation

| Feature | Jira | Confluence | Filament PM | Notes |
|---------|------|------------|-------------|-------|
| **Documents/Wiki** | 🔶 Basic wiki | ✅ Full documentation | ✅ Documents with content | Confluence: Page hierarchy; Filament PM: Flat with linking |
| **Rich Text Editor** | ✅ Advanced | ✅ Advanced | ✅ Filament forms (markdown planned) | Roadmap: Rich text with markdown support |
| **AI Semantic Search** | ❌ Requires expensive addon | ❌ Requires addon | ✅ **Built-in with embeddings** | **Filament PM unique advantage**: Vector similarity search |
| **Document Versioning** | ✅ Page versions | ✅ Full history | ✅ Version history + restore | **Already implemented!** |
| **Real-time Collaboration** | ✅ Concurrent editing | ✅ Real-time | ❌ Not implemented | Roadmap: Live collaboration |
| **Document Templates** | ✅ Blueprints | ✅ Templates | ❌ Not implemented | Roadmap: Document templates |
| **Code Blocks** | ✅ Syntax highlighting | ✅ Code blocks | ❌ Not implemented | Planned: Markdown code blocks |
| **File Attachments** | ✅ Direct uploads | ✅ Attachments | 🔶 Via document linking | Planned: Direct file uploads |
| **Page Hierarchy** | ❌ N/A | ✅ Nested pages | ❌ Flat structure | Architectural decision: Use linking vs hierarchy |
| **Export (PDF/MD)** | ✅ Multiple formats | ✅ Multiple formats | ❌ Not implemented | Roadmap: Export functionality |
| **Inline Comments** | ✅ Comment on text | ✅ Inline comments | ❌ Not implemented | Roadmap: Annotation system |
| **Table of Contents** | ✅ Auto-generated | ✅ Auto TOC | ❌ Not implemented | Planned: Document structure parsing |

### Security, Permissions & Access

| Feature | Jira | Confluence | Filament PM | Notes |
|---------|------|------------|-------------|-------|
| **User Roles & Permissions** | ✅ Granular permissions | ✅ Space permissions | ✅ Role-based (Filament Shield) | Filament PM: Product Manager, Developer, Viewer |
| **Project-Level Access** | ✅ Project permissions | ✅ Space restrictions | ✅ Project membership + invitations | Filament PM: Member-based access control |
| **Project Invitations** | ✅ Email invitations | ✅ Email invitations | ✅ Invitation system with expiration | **Already implemented!** |
| **Granular Permissions** | ✅ Per-issue security | ✅ Page restrictions | ✅ Policies per resource | Filament PM: Policy-based authorization |
| **Two-Factor Authentication** | ✅ 2FA available | ✅ 2FA available | ✅ Via Fortify/Spark | Requires configuration |
| **Social Login** | ✅ Available | ✅ Available | ✅ Socialite integration | **Already implemented!** |
| **SSO/SAML Integration** | ✅ Enterprise tier | ✅ Enterprise tier | 🔶 Requires setup | Roadmap: Native SSO integration |
| **Audit Logs** | ✅ Full audit logs | ✅ Activity logs | 🔶 Basic timestamps | Planned: Detailed audit trail |
| **Self-Hosted Security** | ✅ Data Center (expensive) | ✅ Data Center (expensive) | ✅ Full control | **Filament PM advantage**: You control security |
| **Data Export** | ✅ Full export | ✅ Full export | ✅ Database access | Filament PM: Direct database access |
| **Backup & Restore** | ✅ Cloud-managed | ✅ Cloud-managed | ✅ Your responsibility | Filament PM: Standard Laravel backups |

### Extensibility, Integration & Operations

| Feature | Jira | Confluence | Filament PM | Notes |
|---------|------|------------|-------------|-------|
| **API Availability** | ✅ REST, GraphQL | ✅ REST, GraphQL | ✅ Laravel API routes | Filament PM: Build your own API |
| **Webhooks** | ✅ Extensive webhooks | ✅ Webhooks | 🔶 Via Laravel events | Planned: Native webhook system |
| **Third-Party Integrations** | ✅ Huge marketplace | ✅ Huge marketplace | 🔶 Community-built | Filament PM: Build your own integrations |
| **Custom Fields** | ✅ Full custom fields | ✅ Custom properties | 🔶 Via migrations | Filament PM: Database-level customization |
| **Automation** | ✅ Automation rules | ✅ Automation | 🔶 Via Laravel jobs | Filament PM: Code-based automation |
| **Plugins/Marketplace** | ✅ Thousands of apps | ✅ Thousands of apps | ❌ No marketplace | **Trade-off**: Unlimited customization vs pre-built apps |
| **Theming & Branding** | 🔶 Limited | 🔶 Limited | ✅ Full Filament theming | **Filament PM advantage**: Complete UI control |
| **Email Notifications** | ✅ Advanced notifications | ✅ Notifications | ✅ Laravel notifications | Filament PM: Standard Laravel mail |
| **Database Requirements** | ✅ Managed (cloud) | ✅ Managed (cloud) | ✅ SQLite/MySQL/Postgres | **Filament PM advantage**: Choice of database |
| **Hosting Flexibility** | 🔶 Cloud or expensive DC | 🔶 Cloud or expensive DC | ✅ Any PHP host | **Filament PM advantage**: Run anywhere |
| **Maintenance Overhead** | ❌ Atlassian-managed | ❌ Atlassian-managed | ✅ Your control | **Trade-off**: Control vs convenience |
| **Update Process** | ✅ Automatic (cloud) | ✅ Automatic (cloud) | ✅ `composer update` | Filament PM: You control when to update |
| **Community & Support** | ✅ Official support paid | ✅ Official support paid | 🔶 Community only | Filament PM: Self-hosted = self-supported |
| **Cost Structure** | ❌ Per-user licensing | ❌ Per-user licensing | ✅ FREE | **Filament PM advantage**: Zero licensing fees |

### Pros & Cons Summary

#### Filament PM - Freedom & Ownership

| Perspective | Pros | Cons |
|-------------|------|------|
| **UX** | • Clean, focused interface - no bloat<br>• Customizable status workflows<br>• Built-in Kanban (global + per-project)<br>• Modern Filament UI with dark mode | • No native mobile app yet<br>• Fewer pre-built views (Timeline/Gantt planned)<br>• Non-technical users may prefer Atlassian's familiarity |
| **Technical** | • **Full data ownership** - self-hosted, no AI training on your data<br>• **AI-powered semantic search** built-in (Confluence doesn't have this)<br>• **Extend freely** - modify core, add features, no app marketplace limitations<br>• Laravel/PHP stack - easy to hire developers | • Requires in-house DevOps or hosting knowledge<br>• Community-driven support (no paid support tier)<br>• You control updates, security patches |
| **Cost** | • **Zero licensing fees** - ever<br>• **No per-user pricing** - scale without penalty<br>• **No vendor lock-in** - your data, your code<br>• Hosting costs scale with your usage, not seats | • Requires development resources for custom needs<br>• No SLA guarantee (you control your uptime)<br>• Hidden "cost" is time invested in setup |

#### Atlassian (Jira + Confluence)

| Perspective | Pros | Cons |
|-------------|------|------|
| **UX** | • Polished, familiar interface<br>• Native mobile apps<br>• Extensive keyboard shortcuts | • Overwhelming for simple use cases<br>• Performance degrades with large data<br>• Inconsistent UX between Jira/Confluence |
| **Technical** | • Enterprise features (SSO, audit logs)<br>• Large plugin marketplace<br>• Cloud with 99.9% SLA | • **No native AI semantic search**<br>• **Limited customization** without expensive apps<br>• **Data used for AI training** (unless paid tier)<br>• Plugin conflicts are common |
| **Cost** | • No maintenance (cloud)<br>• Predictable pricing | • **Expensive per-user licensing**<br>• **Vendor lock-in** - difficult migration<br>• **Extra costs for essential features** (advanced search, more storage)<br>• Pricing increases over time |

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

## Docker Deployment

Filament PM includes Docker support for easy containerized deployment.

### Using Docker Compose (Recommended for Local Development)

```bash
# Build and start the container
docker-compose up -d

# Access the application
open http://localhost:8080/admin
```

The container will:
- Automatically run migrations for SQLite on first start
- Persist the database in `./database/database.sqlite`
- Serve the application on port 8080

### Using Pre-built Images from GitHub Container Registry

```bash
# Pull the latest image
docker pull ghcr.io/YOUR_USERNAME/filament-pm:latest

# Run the container
docker run -d \
  --name filament-pm \
  -p 8080:80 \
  -v $(pwd)/database:/var/www/html/database \
  ghcr.io/YOUR_USERNAME/filament-pm:latest
```

### Building Your Own Image

```bash
# Build the image
docker build -t filament-pm:latest .

# Run the container
docker run -d \
  --name filament-pm \
  -p 8080:80 \
  -v $(pwd)/database:/var/www/html/database \
  filament-pm:latest
```

### Environment Variables

You can override environment variables at runtime:

```bash
docker run -d \
  --name filament-pm \
  -p 8080:80 \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=your-db-host \
  -e DB_DATABASE=filament_pm \
  -e DB_USERNAME=your-user \
  -e DB_PASSWORD=your-password \
  ghcr.io/YOUR_USERNAME/filament-pm:latest
```

**Note:** When `DB_CONNECTION` is set to anything other than `sqlite`, migrations will NOT run automatically. You'll need to run them manually:

```bash
docker exec filament-pm php artisan migrate --force
```

### Automated Docker Builds

When you push a git tag matching `v*.*.*` (e.g., `v1.0.0`), GitHub Actions will automatically:

1. Build a Docker image
2. Push it to GitHub Container Registry (GHCR)
3. Tag it with the version number and `latest`

Example:

```bash
git tag v1.0.0
git push origin v1.0.0
```

The image will be available at: `ghcr.io/YOUR_USERNAME/filament-pm:v1.0.0`

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
├── Enums/               # PHP 8.1 enums
│   ├── TaskStatus.php   # Task status enum with translations & colors
│   └── TaskPriority.php # Task priority enum (UI integration pending)
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
