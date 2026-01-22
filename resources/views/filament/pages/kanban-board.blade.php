<x-filament-panels::page>
    <div class="py-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
            Kanban Board
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            View and manage tasks across all projects.
        </p>

        {{ \App\Filament\Widgets\KanbanWidget::make() }}
    </div>
</x-filament-panels::page>
