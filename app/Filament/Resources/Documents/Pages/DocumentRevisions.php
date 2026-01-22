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
