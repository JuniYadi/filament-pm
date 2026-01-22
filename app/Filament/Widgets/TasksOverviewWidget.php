<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TasksOverviewWidget extends BaseWidget
{
    protected ?string $pollingInterval = '15s';

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $user = Auth::user();

        $query = Task::query();

        if (! $user->hasRole('Product Manager')) {
            $query->whereIn('project_id', $user->projects()->pluck('id'));
        }

        $total = $query->count();
        $backlog = (clone $query)->where('status', 'Backlog')->count();
        $inProgress = (clone $query)->where('status', 'In Progress')->count();
        $done = (clone $query)->where('status', 'Done')->count();

        return [
            Stat::make('Total Tasks', $total)
                ->description('All tasks')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('gray'),

            Stat::make('In Progress', $inProgress)
                ->description('Active tasks')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('primary'),

            Stat::make('Done', $done)
                ->description('Completed tasks')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
