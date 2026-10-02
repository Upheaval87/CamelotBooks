@props(['task', 'modal' => false, 'bucket' => ''])

@if($modal)
    {{-- Modal fragment: legacy markup, unchanged. --}}
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
        </div>
    </div>
@else
    @php
        $overdue = $task->isOverdue();
        $hasDeadline = (bool) ($task->deadline_granularity || $task->deadline_date);
        $priorityMap = [
            'low' => ['low', __('Low')],
            'medium' => ['med', __('Medium')],
            'high' => ['high', __('High')],
        ];
        [$prioClass, $prioLabel] = $priorityMap[$task->priority] ?? ['med', ucfirst($task->priority)];
    @endphp

    <div
        class="todo-row task {{ $overdue ? 'overdue' : '' }}"
        x-show="matchesRow($el)"
        data-task="{{ $task->id }}"
        data-status="active"
        data-title="{{ \Illuminate\Support\Str::lower($task->title) }}"
        data-priority="{{ $task->priority }}"
        data-overdue="{{ $overdue ? '1' : '0' }}"
        data-bucket="{{ $bucket }}"
        data-granularity="{{ $task->deadline_granularity ?? '' }}"
    >
        <form method="POST" action="{{ route('todo.complete', $task) }}">
            @csrf
            <button type="submit" class="tcheck" title="{{ __('Mark complete') }}" aria-label="{{ __('Mark complete') }}">
                <span class="box">
                    <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </button>
        </form>

        <div class="t-main">
            <button
                type="button"
                class="todo-row-title t-title"
                title="{{ __('View task') }}"
                @click="$dispatch('open-task-detail', {
                    taskId: {{ $task->id }},
                    title: @js($task->title),
                    priority: @js($task->priority),
                    deadlineGranularity: @js($task->deadline_granularity ?? ''),
                    deadlineDate: @js($task->deadline_date?->format('Y-m-d') ?? ''),
                    deadlineLabel: @js($task->deadlineLabel()),
                    isOverdue: {{ $overdue ? 'true' : 'false' }},
                    updateUrl: @js(route('todo.update', $task)),
                    deleteUrl: @js(route('todo.destroy', $task)),
                    linkableType: @js($task->linkable_type ?? ''),
                    linkableId: @js($task->linkable_id ?? ''),
                    linkLabel: @js($task->link_label ?? ''),
                    linkUrl: @js($task->link_url ?? ''),
                })"
            >
                {{ $task->title }}
            </button>

            <div class="t-meta">
                <span class="mchip {{ $prioClass }}">{{ $prioLabel }}</span>

                @if(! $hasDeadline)
                    <span class="mchip none">
                        <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('No deadline') }}
                    </span>
                @elseif($overdue)
                    <span class="mchip overdue">
                        <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $task->deadlineLabel() }}
                    </span>
                @else
                    <span class="mchip due">
                        <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $task->deadlineLabel() }}
                    </span>
                @endif

                @if($task->link_label)
                    @if($task->linkable)
                        <a href="{{ $task->link_url }}" class="mchip link" target="_blank" rel="noopener" @click.stop>
                            <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 015.656 0l3 3a4 4 0 01-5.657 5.657l-1.5-1.5M10.172 13.828a4 4 0 01-5.656 0l-3-3a4 4 0 015.657-5.657l1.5 1.5"/></svg>
                            {{ $task->link_label }}
                        </a>
                    @else
                        <span class="mchip link is-muted" title="{{ __('Linked record no longer available') }}">
                            <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 015.656 0l3 3a4 4 0 01-5.657 5.657l-1.5-1.5M10.172 13.828a4 4 0 01-5.656 0l-3-3a4 4 0 015.657-5.657l1.5 1.5"/></svg>
                            {{ $task->link_label }}
                        </span>
                    @endif
                @endif
            </div>
        </div>

        <div class="t-actions">
            <button
                type="button"
                class="t-btn"
                title="{{ __('Open') }}"
                aria-label="{{ __('Open') }}"
                @click="$dispatch('open-task-detail', {
                    taskId: {{ $task->id }},
                    title: @js($task->title),
                    priority: @js($task->priority),
                    deadlineGranularity: @js($task->deadline_granularity ?? ''),
                    deadlineDate: @js($task->deadline_date?->format('Y-m-d') ?? ''),
                    deadlineLabel: @js($task->deadlineLabel()),
                    isOverdue: {{ $overdue ? 'true' : 'false' }},
                    updateUrl: @js(route('todo.update', $task)),
                    deleteUrl: @js(route('todo.destroy', $task)),
                    linkableType: @js($task->linkable_type ?? ''),
                    linkableId: @js($task->linkable_id ?? ''),
                    linkLabel: @js($task->link_label ?? ''),
                    linkUrl: @js($task->link_url ?? ''),
                })"
            >
                <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>

            <form method="POST" action="{{ route('todo.destroy', $task) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Delete this task permanently?') }}', { type: 'danger' });">
                @csrf
                @method('DELETE')
                <button type="submit" class="t-btn del" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                    <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
        </div>
    </div>
@endif
