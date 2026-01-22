<div x-data="kanbanBoard({{ json_encode($statuses) }}, @entangle('columns'))"
     x-init="initBoard()"
     class="kanban-board">
    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($statuses as $status)
            <div class="kanban-column min-w-[300px] w-80 flex-shrink-0 bg-gray-50 dark:bg-gray-800 rounded-lg p-4"
                 data-status="{{ $status }}">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </h3>
                    <span class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs px-2 py-1 rounded-full">
                        {{ count($columns[$status] ?? []) }}
                    </span>
                </div>

                <div class="kanban-tasks space-y-2 min-h-[200px]"
                     data-status="{{ $status }}"
                     wire:sortable
                     wire:sortable-end="updateTaskOrder">
                    @foreach(($columns[$status] ?? []) as $task)
                        <div wire:sortable.item="{{ $task['id'] }}"
                             class="kanban-task-card bg-white dark:bg-gray-900 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-move hover:shadow-md transition-shadow">
                            <h4 class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-2">
                                {{ $task['title'] }}
                            </h4>
                            @if($task['description'])
                                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                                    {{ Str::limit($task['description'], 100) }}
                                </p>
                            @endif
                            @if($task['assigned_to'])
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-xs text-gray-500">
                                        Assigned to: {{ $task['assigned_to'] }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
    function kanbanBoard(statuses, columns) {
        return {
            statuses: statuses,
            columns: columns,

            initBoard() {
                // Initialize drag and drop if needed
                // Filament's wire:sortable handles most of this
            }
        }
    }
</script>
@endpush
