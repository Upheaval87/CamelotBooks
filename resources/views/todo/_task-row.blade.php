<div class="todo-row {{ $task->isOverdue() ? 'is-overdue' : '' }}">
    <div class="todo-row-main">
        <form method="POST" action="{{ route('todo.complete', $task) }}">
            @csrf
            <button type="submit" class="todo-check" title="{{ __('Mark complete') }}" aria-label="{{ __('Mark complete') }}"></button>
        </form>

        <button
            type="button"
            class="todo-row-title"
            @click="$dispatch('open-task-detail', {
                taskId: {{ $task->id }},
                title: @js($task->title),
                priority: @js($task->priority),
                deadlineGranularity: @js($task->deadline_granularity ?? ''),
                deadlineDate: @js($task->deadline_date?->format('Y-m-d') ?? ''),
                deadlineLabel: @js($task->deadlineLabel()),
                isOverdue: {{ $task->isOverdue() ? 'true' : 'false' }},
                updateUrl: @js(route('todo.update', $task)),
                deleteUrl: @js(route('todo.destroy', $task)),
                linkableType: @js($task->linkable_type ?? ''),
                linkableId: @js($task->linkable_id ?? ''),
                linkLabel: @js($task->link_label ?? ''),
                linkUrl: @js($task->link_url ?? ''),
            })"
            title="{{ __('View task') }}"
        >
            {{ $task->title }}
        </button>

        <span class="todo-priority-dot todo-priority-{{ $task->priority }}" title="{{ ucfirst($task->priority) }}"></span>

        <span class="todo-deadline-label {{ $task->isOverdue() ? 'text-danger' : '' }}">{{ $task->deadlineLabel() }}</span>

        @if($task->link_label)
            @if($task->linkable)
                <a href="{{ $task->link_url }}" class="todo-link-chip" target="_blank" rel="noopener" @click.stop>
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.656 0l3 3a4 4 0 01-5.657 5.657l-1.5-1.5M10.172 13.828a4 4 0 01-5.656 0l-3-3a4 4 0 015.657-5.657l1.5 1.5"/></svg>
                    <span>{{ $task->link_label }}</span>
                </a>
            @else
                <span class="todo-link-chip is-muted" title="{{ __('Linked record no longer available') }}">
                    <span>{{ $task->link_label }}</span>
                </span>
            @endif
        @endif

        <form method="POST" action="{{ route('todo.destroy', $task) }}" class="ml-auto" onsubmit="return fbConfirmSubmit(event, '{{ __('Delete this task permanently?') }}', { type: 'danger' });">
            @csrf
            @method('DELETE')
            <button type="submit" class="icon-btn todo-delete-btn" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </form>
    </div>
</div>
