@props(['task', 'modal' => false])

<div class="todo-row is-completed">
    <div class="todo-row-main">
        <form method="POST" action="{{ route('todo.reopen', $task) }}">
            @csrf
            <button type="submit" class="todo-check is-checked" title="{{ __('Reopen task') }}" aria-label="{{ __('Reopen task') }}">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </button>
        </form>

        <span class="todo-row-title">{{ $task->title }}</span>

        <span class="todo-priority-dot todo-priority-{{ $task->priority }}" title="{{ ucfirst($task->priority) }}"></span>

        <span class="todo-deadline-label">{{ $task->deadlineLabel() }}</span>

        @if($task->link_label)
            @if($task->linkable)
                <a href="{{ $task->link_url }}" class="todo-link-chip" target="_blank" rel="noopener">
                    <span>{{ $task->link_label }}</span>
                </a>
            @else
                <span class="todo-link-chip is-muted" title="{{ __('Linked record no longer available') }}">
                    <span>{{ $task->link_label }}</span>
                </span>
            @endif
        @endif

        @if($modal)
            <button
                type="button"
                class="icon-btn todo-delete-btn ml-auto"
                title="{{ __('Delete') }}"
                aria-label="{{ __('Delete') }}"
                @click="$dispatch('todo-delete', {
                    id: {{ $task->id }},
                    url: @js(route('todo.destroy', $task)),
                    title: @js($task->title),
                })"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        @else
        <form method="POST" action="{{ route('todo.destroy', $task) }}" class="ml-auto" onsubmit="return fbConfirmSubmit(event, '{{ __('Delete this task permanently?') }}', { type: 'danger' });">
            @csrf
            @method('DELETE')
            <button type="submit" class="icon-btn todo-delete-btn" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </form>
        @endif
    </div>
</div>
