<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $companyId = session('current_company_id');
            $currencySymbol = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');
        @endphp
        <meta name="currency-symbol" content="{{ $currencySymbol }}">

        <title>{{ config('app.name', 'CamelotBooks') }}</title>

        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#128F8E">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <script>
            window.favouritesIndexUrl = "{{ route('favourites.index') }}";
            window.favouritesStoreUrl = "{{ route('favourites.store') }}";
            window.favouritesDestroyUrl = "{{ route('favourites.destroy', ['pageKey' => ':pageKey']) }}";
            window.favouritesReorderUrl = "{{ route('favourites.reorder') }}";
            window.favouritesPreferencesUrl = "{{ route('favourites.preferences') }}";
            window.favouritesPagesUrl = "{{ route('favourites.pages') }}";
            window.todoIndexUrl = "{{ route('todo.index') }}";
            window.todoModalUrl = "{{ route('todo.modal') }}";
            window.TODO_LINKABLE_CLASS_MAP = @json(\App\Models\TodoTask::LINKABLE_CLASS_MAP);
        </script>

        @if(!\Illuminate\Support\Facades\Vite::isRunningHot())
            <script src="{{ \Illuminate\Support\Facades\Vite::asset('resources/js/scoped-search-field.js') }}"></script>
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-neutral-50 dark:bg-neutral-950">

        @include('layouts.topbar-two-row')

        <div class="flex flex-col min-h-screen">
            <div class="flex flex-1 min-h-0">
                @php
                    $routeName = request()->route()?->getName() ?? '';
                    $favouriteMeta = \App\Services\FavouritesService::metaForRoute($routeName);
                    if ($favouriteMeta === null) {
                        $favouriteMeta = \App\Services\FavouritesService::metaForRecord($routeName, $header ?? '');
                    }
                @endphp
                <x-favourites.sidebar :favourite-meta="$favouriteMeta" :favourite-override="isset($favourite) ? true : false" />
                <main class="flex-1 min-w-0 pb-6 lg:pb-8 max-w-8xl mx-auto w-full">
                    <div class="animate-fade-in-up">
                        @isset($favourite)
                            <div class="shrink-0">
                                {{ $favourite }}
                            </div>
                        @endisset
                        {{ $slot }}
                    </div>
                </main>
                @if(isset($favouriteMeta) && !isset($favourite))
                    <div class="fav-float-toggle"
                         x-data="{ store: $store.favourites }"
                         x-show="!store.pinned"
                         x-cloak>
                        <x-favourite-toggle :page-key="$favouriteMeta['key']" :label="$favouriteMeta['label']" :icon="$favouriteMeta['icon']" :url="$favouriteMeta['url']" />
                    </div>
                @endif
            </div>
        </div>


        {{-- Feedback system: toasts viewport (JS-created), flash emitter, confirm modal root --}}
        <x-feedback.flashes />
        <div id="feedback-confirm-root"></div>

        {{-- Global search modal --}}
        <x-global-search-modal :search-url="route('accounting.search.global')" />

        {{-- My Tasks modal (opened from the topbar, no page reload) --}}
        <x-modal name="my-tasks" maxWidth="4xl">
            <div class="todo-modal-shell" x-data="todoModal()"
                 @open-modal.window="$event.detail === 'my-tasks' ? refresh() : null"
                 @todo-delete.window="onDelete($event.detail)"
                 @todo-refresh.window="refresh()">
                <div class="todo-modal-head">
                    <div>
                        <p class="todo-modal-eyebrow">{{ __('Personal') }}</p>
                        <h3 class="todo-modal-title">{{ __('My Tasks') }}</h3>
                    </div>
                    <button type="button" class="icon-btn" title="{{ __('Close') }}" aria-label="{{ __('Close') }}" @click="$dispatch('close-modal', 'my-tasks')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="todo-modal-body" x-ref="wrap">
                    <div x-ref="list"></div>
                </div>
            </div>
        </x-modal>

        {{-- Shared task detail modal. Global so both the /todo page and the
             My Tasks modal can open it; listen for the row's open-task-detail event. --}}
        <div
            x-data="todoDetailModal()"
            @open-task-detail.window="openTask($event.detail)"
        >
            <x-modal name="task-detail" maxWidth="lg">
                <div class="todo-modal" @item-selected="onLinkSelected($event)">
                    <form method="POST" :action="updateUrl" id="task-update-form" @submit.prevent="saveUpdate($event)">
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
                                type="button"
                                class="btn-danger btn-md"
                                @click="confirmDelete()"
                            >{{ __('Delete') }}</button>
                            <div class="flex gap-3">
                                <button type="button" class="btn-ghost btn-md" @click="$dispatch('close-modal', 'task-detail')">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn-primary btn-md">{{ __('Save Changes') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </x-modal>
        </div>

        <script>
            window.currencySymbol = document.querySelector('meta[name="currency-symbol"]')?.getAttribute('content') || '$';
            window.formatMoney = function(amount) {
                var val = parseFloat(amount) || 0;
                var negative = val < 0 ? '-' : '';
                var formatted = Math.abs(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return negative + window.currencySymbol + formatted;
            };
            window.formatNumber = function(amount) {
                var val = parseFloat(amount) || 0;
                var negative = val < 0 ? '-' : '';
                return negative + Math.abs(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };
            window.currencySuffix = function(label) {
                return label + ' (' + window.currencySymbol + ')';
            };

            window.atlasToast = function(message, type) {
                if (window.feedback) window.feedback.toast(type || 'success', message);
            };

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(function(){});
            }
        </script>
    </body>
</html>
