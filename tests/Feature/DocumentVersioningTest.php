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

        // Assert versions were created (initial + after update)
        $this->assertCount(2, $document->versions);
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
        $document->revertToVersion($firstVersion->id);

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
