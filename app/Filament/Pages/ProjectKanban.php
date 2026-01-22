<?php

namespace App\Filament\Pages;

use App\Models\Project;
use Filament\Pages\Page;

class ProjectKanban extends Page
{
    protected static string $view = 'filament.pages.project-kanban';

    protected static bool $shouldRegisterNavigation = false;

    public Project $record;

    public function mount(Project $record): void
    {
        $this->record = $record;
    }
}
