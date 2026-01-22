<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class KanbanBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static string $view = 'filament.pages.kanban-board';

    protected static ?string $navigationLabel = 'Kanban Board';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view any kanban board') ?? true;
    }
}
