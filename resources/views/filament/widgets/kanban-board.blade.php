<div class="kanban-board">
    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($statuses as $status)
            <div class="kanban-column min-w-[300px] w-80 flex-shrink-0 bg-gray-50 dark:bg-gray-800 rounded-lg p-4"
                 data-status="{{ $status }}"
                 ondrop="drop(event, '{{ $status }}')"
                 ondragover="allowDrop(event)">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </h3>
                    <span class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs px-2 py-1 rounded-full">
                        {{ count($columns[$status] ?? []) }}
                    </span>
                </div>

                <div class="kanban-tasks space-y-2 min-h-[200px]">
                    @foreach(($columns[$status] ?? []) as $task)
                        <div
                            draggable="true"
                            ondragstart="drag(event, {{ $task['id'] }})"
                            data-id="{{ $task['id'] }}"
                            class="kanban-task-card bg-white dark:bg-gray-900 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-move hover:shadow-md transition-shadow"
                        >
                            <h4 class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-2">
                                {{ $task['title'] }}
                            </h4>
                            @if($task['description'])
                                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                                    {{ \Illuminate\Support\Str::limit($task['description'], 100) }}
                                </p>
                            @endif
                            @if($task['assigned_to'] || $task['due_date'])
                                <div class="mt-2 flex items-center gap-2">
                                    @if($task['assigned_to'])
                                        <span class="text-xs text-gray-500">
                                            Assigned to: {{ $task['assigned_to'] }}
                                        </span>
                                    @endif
                                    @if($task['due_date'])
                                        <span class="text-xs {{ $task['is_overdue'] ? 'text-danger-600 font-medium' : 'text-gray-500' }}">
                                            Due: {{ \Carbon\Carbon::parse($task['due_date'])->format('M j') }}
                                            @if($task['is_overdue'])
                                                <span class="text-danger-600">!</span>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    function drag(event, taskId) {
        event.dataTransfer.setData("taskId", taskId);
        event.target.classList.add('opacity-50');
    }

    function allowDrop(event) {
        event.preventDefault();
    }

    function drop(event, status) {
        event.preventDefault();
        const taskId = event.dataTransfer.getData("taskId");

        // Remove opacity from all cards
        document.querySelectorAll('.kanban-task-card').forEach(card => {
            card.classList.remove('opacity-50');
        });

        // Call Livewire method to update status
        @this.updateTaskStatus(taskId, status);
    }
</script>
