<x-filament-panels::page>
    <div class="py-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $record->name }} - Kanban
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $record->description }}
                </p>
            </div>
            <x-filament::link
                :href="\App\Filament\Resources\Projects\ProjectResource::getUrl('edit', ['record' => $record])"
                color="gray"
            >
                Back to Project
            </x-filament::link>
        </div>

        {{ \App\Filament\Widgets\KanbanWidget::make()->projectSlug($record->slug) }}
    </div>
</x-filament-panels::page>
