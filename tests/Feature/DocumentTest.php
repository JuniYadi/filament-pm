<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_document(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $document = Document::factory()->create([
            'created_by' => $user->id,
            'project_id' => $project->id,
        ]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'title' => $document->title,
        ]);
    }

    public function test_document_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $document->createdBy);
        $this->assertEquals($user->id, $document->createdBy->id);
    }

    public function test_document_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $document = Document::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $document->project);
        $this->assertEquals($project->id, $document->project->id);
    }

    public function test_document_has_slug(): void
    {
        $document = Document::factory()->create([
            'title' => 'My Test Document',
            'slug' => 'my-test-document',
        ]);

        $this->assertEquals('my-test-document', $document->slug);
    }

    public function test_document_embedding_is_cast_to_array(): void
    {
        $embedding = [0.1, 0.2, 0.3, 0.4];
        $document = Document::factory()->create([
            'embedding' => $embedding,
        ]);

        $this->assertIsArray($document->embedding);
        $this->assertEquals($embedding, $document->embedding);
    }

    public function test_document_can_be_linked_to_tasks(): void
    {
        $task = Task::factory()->create();
        $document = Document::factory()->create();

        $task->documents()->attach($document->id);

        $this->assertTrue($task->documents->contains($document));
        $this->assertTrue($document->tasks->contains($task));
    }

    public function test_document_creator_can_view_document(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create(['created_by' => $user->id]);

        $this->assertTrue($user->can('view', $document));
    }

    public function test_project_member_can_view_project_document(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id, ['role' => 'Developer']);

        $document = Document::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $this->assertTrue($member->can('view', $document));
    }

    public function test_non_member_cannot_view_project_document(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $document = Document::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->assertFalse($user->can('view', $document));
    }

    public function test_document_creator_can_update_document(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create(['created_by' => $user->id]);

        $this->assertTrue($user->can('update', $document));
    }

    public function test_document_creator_can_delete_document(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create(['created_by' => $user->id]);

        $this->assertTrue($user->can('delete', $document));
    }

    public function test_non_creator_cannot_delete_document(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create();

        $this->assertFalse($user->can('delete', $document));
    }

    public function test_generate_embedding_creates_vector(): void
    {
        // Mock the EmbeddingService
        $mockService = Mockery::mock(EmbeddingService::class);
        $mockService->shouldReceive('generateEmbedding')
            ->once()
            ->with('Test Title Test Content')
            ->andReturn([0.1, 0.2, 0.3, 0.4]);

        App::instance(EmbeddingService::class, $mockService);

        $document = Document::factory()->make([
            'title' => 'Test Title',
            'content' => 'Test Content',
            'embedding' => null,
        ]);
        $document->save();

        $this->assertEquals([0.1, 0.2, 0.3, 0.4], $document->refresh()->embedding);
    }

    public function test_find_similar_returns_sorted_documents(): void
    {
        $mockService = Mockery::mock(EmbeddingService::class);
        $mockService->shouldReceive('generateEmbedding')
            ->once()
            ->with('search query')
            ->andReturn([1.0, 0.0]);

        // Only one document will be compared (document2), document1 is excluded
        $mockService->shouldReceive('cosineSimilarity')
            ->once()
            ->andReturn(0.0);

        // Allow any other calls
        $mockService->shouldReceive('generateEmbedding')
            ->byDefault()
            ->andReturn([]);

        App::instance(EmbeddingService::class, $mockService);

        $document1 = Document::factory()->make([
            'title' => 'Similar Document',
            'content' => '',
            'embedding' => [1.0, 0.0],
        ]);
        $document1->saveQuietly();

        $document2 = Document::factory()->make([
            'title' => 'Different Document',
            'content' => '',
            'embedding' => [0.0, 1.0],
        ]);
        $document2->saveQuietly();

        $results = $document1->findSimilar('search query', limit: 5);

        $this->assertCount(1, $results);
        $this->assertEquals($document2->id, $results->first()->id);
    }

    public function test_find_similar_excludes_self(): void
    {
        $mockService = Mockery::mock(EmbeddingService::class);
        $mockService->shouldReceive('generateEmbedding')
            ->once()
            ->andReturn([1.0, 0.0]);

        // Allow any other calls
        $mockService->shouldReceive('generateEmbedding')
            ->byDefault()
            ->andReturn([]);

        $mockService->shouldReceive('cosineSimilarity')
            ->once()
            ->andReturn(0.5);

        App::instance(EmbeddingService::class, $mockService);

        $document1 = Document::factory()->make([
            'embedding' => [1.0, 0.0],
        ]);
        $document1->saveQuietly();

        $document2 = Document::factory()->make([
            'embedding' => [1.0, 0.0],
        ]);
        $document2->saveQuietly();

        $results = $document1->findSimilar('query');

        $this->assertCount(1, $results);
        $this->assertNotEquals($document1->id, $results->first()->id);
    }
}
