<x-app-layout>
    <x-slot name="header">
        {{ __('My Tasks') }}
    </x-slot>

    <script>
        window.TODO_LINKABLE_CLASS_MAP = @json(\App\Models\TodoTask::LINKABLE_CLASS_MAP);
    </script>

    @php
        $bucketLabels = [
            \App\Models\TodoTask::BUCKET_OVERDUE => __('Overdue'),
            \App\Models\TodoTask::BUCKET_TODAY => __('Today'),
            \App\Models\TodoTask::BUCKET_THIS_MONTH => __('This Month'),
            \App\Models\TodoTask::BUCKET_THIS_YEAR => __('This Year'),
            \App\Models\TodoTask::BUCKET_NO_DEADLINE => __('No Deadline'),
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">

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
                <div x-data="{ tab: 'active' }">
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
                                            @include('todo._task-row', ['task' => $task])
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
                                    @include('todo._task-row-completed', ['task' => $task])
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Shared task detail modal. Rendered at page level, OUTSIDE the .todo-list card
                 (which has overflow:hidden). Fixed positioning now works because .animate-fade-in-up
                 no longer retains a transform (fill mode changed both -> backwards). --}}
            <div
                x-data="todoDetailModal()"
                @open-task-detail.window="openTask($event.detail)"
            >
                <x-modal name="task-detail" maxWidth="lg">
                    <div class="todo-modal" @item-selected="onLinkSelected($event)">
                        <form method="POST" :action="updateUrl" id="task-update-form">
                            @csrf
                            @method('PUT')

                            {{-- Title row --}}
                            <div class="todo-modal-header">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <span class="todo-priority-dot" :class="'todo-priority-' + priority" style="width:10px;height:10px;flex-shrink:0"></span>
                                    <input
                                        type="text"
                                        name="title"
                                        x-model="title"
                                        class="todo-modal-title-input"
                                        required
                                        maxlength="255"
                                    />
                                </div>
                                <span class="todo-modal-status" :class="isOverdue ? 'is-overdue' : ''" x-text="deadlineLabel"></span>
                            </div>

                            {{-- Linked record (prominent display) --}}
                            <div class="todo-modal-section">
                                <h4 class="todo-modal-section-label">{{ __('Linked Record') }}</h4>
                                <div x-show="linkLabel" x-cloak>
                                    <div class="todo-modal-link-card" x-show="linkUrl">
                                        <div class="todo-modal-link-card-icon">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.656 0l3 3a4 4 0 01-5.657 5.657l-1.5-1.5M10.172 13.828a4 4 0 01-5.656 0l-3-3a4 4 0 015.657-5.657l1.5 1.5"/></svg>
                                        </div>
                                        <span class="todo-modal-link-card-label" x-text="linkLabel"></span>
                                        <a :href="linkUrl" class="btn-ghost btn-sm todo-modal-link-card-btn" target="_blank" rel="noopener">
                                            {{ __('Open') }}
                                            <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="todo-link-chip-remove-text" @click="clearLink()">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            {{ __('Remove link') }}
                                        </button>
                                    </div>
                                </div>
                                <div x-show="!linkLabel" class="text-sm text-neutral-400 italic">{{ __('No record linked') }}</div>
                            </div>

                            {{-- Deadline --}}
                            <div class="todo-modal-section">
                                <h4 class="todo-modal-section-label">{{ __('Deadline') }}</h4>
                                <div x-data="todoDeadline({ name: 'deadline', granularity: deadlineGranularity, date: deadlineDate })" :key="taskId" class="todo-deadline">
                                    <input type="hidden" :name="name + '_granularity'" x-model="granularity" />
                                    <input type="hidden" :name="name + '_date'" x-model="date" />
                                    <div class="todo-deadline-chips">
                                        <button type="button" @click="pick('day')" :class="chipClass('day', 'today')">{{ __('Today') }}</button>
                                        <button type="button" @click="pick('week')" :class="chipClass('week')">{{ __('This Week') }}</button>
                                        <button type="button" @click="pick('month')" :class="chipClass('month')">{{ __('This Month') }}</button>
                                        <button type="button" @click="pick('year')" :class="chipClass('year')">{{ __('This Year') }}</button>
                                        <button type="button" @click="pickCustom()" :class="chipClass('day', 'custom')">{{ __('Pick a date') }}</button>
                                    </div>
                                    <div x-show="customOpen" x-cloak class="todo-deadline-date">
                                        <input type="date" x-model="date" class="input todo-deadline-date-input" />
                                    </div>
                                    <span class="todo-deadline-preview" x-text="preview"></span>
                                </div>
                            </div>

                            {{-- Priority --}}
                            <div class="todo-modal-section">
                                <h4 class="todo-modal-section-label">{{ __('Priority') }}</h4>
                                <select name="priority" x-model="priority" class="input mt-1 block w-full max-w-[180px]">
                                    <option value="low">{{ __('Low') }}</option>
                                    <option value="medium">{{ __('Medium') }}</option>
                                    <option value="high">{{ __('High') }}</option>
                                </select>
                            </div>

                            {{-- Change linked record --}}
                            <div class="todo-modal-section">
                                <h4 class="todo-modal-section-label">{{ __('Change Link') }}</h4>
                                <div class="todo-link-wrap">
                                    <x-scoped-search-field
                                        name="link_picker"
                                        entity=""
                                        search-url="{{ route('accounting.search.any') }}"
                                        placeholder="{{ __('Search all records…') }}"
                                        allow-global-search
                                    />
                                </div>
                                <input type="hidden" name="linkable_type" :value="linkableType" />
                                <input type="hidden" name="linkable_id" :value="linkableId" />
                                <input type="hidden" name="link_label" :value="linkLabel" />
                                <input type="hidden" name="link_url" :value="linkUrl" />
                            </div>

                            {{-- Footer actions --}}
                            <div class="todo-modal-footer">
                                <button
                                    type="submit"
                                    form="task-delete-form"
                                    class="btn-danger btn-md"
                                >{{ __('Delete') }}</button>
                                <div class="flex gap-3">
                                    <button type="button" class="btn-ghost btn-md" @click="$dispatch('close-modal', 'task-detail')">{{ __('Cancel') }}</button>
                                    <button type="submit" class="btn-primary btn-md">{{ __('Save Changes') }}</button>
                                </div>
                            </div>
                        </form>

                        <form
                            method="POST"
                            :action="deleteUrl"
                            id="task-delete-form"
                            class="hidden"
                            aria-hidden="true"
                            onsubmit="return confirm('{{ __('Delete this task permanently?') }}');"
                        >
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </x-modal>
            </div>

        </div>
    </div>
</x-app-layout>
