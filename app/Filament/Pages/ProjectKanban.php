<?php

namespace App\Filament\Pages;

use App\Models\Project;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Facades\Route;

class ProjectKanban extends Page
{
    protected static string $view = 'filament.pages.project-kanban';

    protected static bool $shouldRegisterNavigation = false;

    public Project $record;

    public function mount(Project $record): void
    {
        $this->record = $record;
    }

    public static function routes(Panel $panel): void
    {
        Route::get('/projects/{record}/kanban', static::class)
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name(static::getRelativeRouteName($panel));
    }
}
