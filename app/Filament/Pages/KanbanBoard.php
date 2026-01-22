<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class KanbanBoard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected string $view = 'filament.pages.kanban-board';

    protected static ?string $navigationLabel = 'Kanban Board';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return true;
    }
}
