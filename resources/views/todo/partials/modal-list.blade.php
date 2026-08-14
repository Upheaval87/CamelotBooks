@php
    $bucketLabels = [
        \App\Models\TodoTask::BUCKET_OVERDUE => __('Overdue'),
        \App\Models\TodoTask::BUCKET_TODAY => __('Today'),
        \App\Models\TodoTask::BUCKET_THIS_MONTH => __('This Month'),
        \App\Models\TodoTask::BUCKET_THIS_YEAR => __('This Year'),
        \App\Models\TodoTask::BUCKET_NO_DEADLINE => __('No Deadline'),
    ];
@endphp

<div data-active-count="{{ $active->count() }}">
    {{-- Quick-Add Composer --}}
    <form method="POST" action="{{ route('todo.store') }}" x-data="todoComposer()" @item-selected="onLinkSelected($event)" class="card p-6">
        @csrf

        <div class="todo-composer-main">
            <input
                type="text"
                name="title"
                required
                maxlength="255"
                autocomplete="off"
                placeholder="{{ __('Add a task… (press Enter to add)') }}"
                class="todo-composer-input"
            />
            <button
                type="button"
                class="todo-composer-toggle"
                @click="open = !open"
                :class="open ? 'is-active' : ''"
                title="{{ __('Deadline & link') }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </button>
            <button type="submit" class="btn-primary btn-md">{{ __('Add') }}</button>
        </div>

        <x-input-error :messages="$errors->get('title')" class="mt-2" />
        <x-input-error :messages="$errors->get('deadline_date')" class="mt-2" />

        <div x-show="open" x-collapse x-cloak class="todo-composer-details">
            <div class="todo-composer-fields">
                <div>
                    <x-input-label value="{{ __('Deadline') }}" />
                    @include('todo._deadline-chips')
                </div>

                <div>
                    <x-input-label value="{{ __('Priority') }}" />
                    <select name="priority" class="input mt-1 block w-full">
                        <option value="low">{{ __('Low') }}</option>
                        <option value="medium" selected>{{ __('Medium') }}</option>
                        <option value="high">{{ __('High') }}</option>
                    </select>
                </div>

                <div>
                    <x-input-label value="{{ __('Link a record') }}" />
                    <div class="todo-link-wrap">
                        <x-scoped-search-field
                            name="link_picker"
                            entity=""
                            search-url="{{ route('accounting.search.any') }}"
                            placeholder="{{ __('Search all records…') }}"
                            allow-global-search
                        />
                        <span x-show="linkLabel" class="todo-link-chip" x-cloak>
                            <a :href="linkUrl" x-text="linkLabel" target="_blank" rel="noopener"></a>
                            <button type="button" class="todo-link-chip-remove" @click="clearLink()" title="{{ __('Remove link') }}">&times;</button>
                        </span>
                    </div>
                    <input type="hidden" name="linkable_type" :value="linkableType" />
                    <input type="hidden" name="linkable_id" :value="linkableId" />
                    <input type="hidden" name="link_label" :value="linkLabel" />
                    <input type="hidden" name="link_url" :value="linkUrl" />
                </div>
            </div>
        </div>
    </form>

    {{-- Active / Completed views --}}
    <div x-data="{ tab: 'active' }" class="mt-6">
        <div class="todo-tabs">
            <button type="button" class="todo-tab" @click="tab = 'active'" :class="tab === 'active' ? 'is-active' : ''">
                {{ __('Active') }} <span class="todo-tab-count">{{ $active->count() }}</span>
            </button>
            <button type="button" class="todo-tab" @click="tab = 'completed'" :class="tab === 'completed' ? 'is-active' : ''">
                {{ __('Completed') }} <span class="todo-tab-count">{{ $completed->count() }}</span>
            </button>
        </div>

        {{-- Active view --}}
        <div x-show="tab === 'active'">
            @if($active->isEmpty())
                <div class="empty-state mt-6">
                    <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <p class="empty-state-title">{{ __('No active tasks') }}</p>
                    <p class="empty-state-text">{{ __('Add a task above to get started.') }}</p>
                </div>
            @else
                @foreach($groups as $bucket => $tasks)
                    @if($tasks->isEmpty())
                        @continue
                    @endif
                    <div class="todo-group">
                        <h3 class="todo-group-header">
                            {{ $bucketLabels[$bucket] }}
                            <span class="todo-group-count">{{ $tasks->count() }}</span>
                        </h3>
                        <div class="card p-0 todo-list">
                            @foreach($tasks as $task)
                                @include('todo._task-row', ['task' => $task, 'modal' => true])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Completed view --}}
        <div x-show="tab === 'completed'" x-cloak>
            @if($completed->isEmpty())
                <div class="empty-state mt-6">
                    <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="empty-state-title">{{ __('Nothing completed yet') }}</p>
                    <p class="empty-state-text">{{ __('Completed tasks will appear here for review.') }}</p>
                </div>
            @else
                <div class="card p-0 todo-list mt-6">
                    @foreach($completed as $task)
                        @include('todo._task-row-completed', ['task' => $task, 'modal' => true])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
