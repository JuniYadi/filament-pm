<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ProjectKanban extends Page
{
    use InteractsWithRecord;

    protected static string $view = 'filament.pages.project-kanban';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
}
