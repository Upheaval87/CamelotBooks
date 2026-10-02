<x-app-layout>
    @php
        $bucketLabels = [
            \App\Models\TodoTask::BUCKET_OVERDUE => __('Overdue'),
            \App\Models\TodoTask::BUCKET_TODAY => __('Today'),
            \App\Models\TodoTask::BUCKET_THIS_MONTH => __('This Month'),
            \App\Models\TodoTask::BUCKET_THIS_YEAR => __('This Year'),
            \App\Models\TodoTask::BUCKET_NO_DEADLINE => __('No Deadline'),
        ];

        $openCount = $active->count();
        $overdueCount = $active->filter(fn ($t) => $t->isOverdue())->count();
        $todayCount = $active->filter(fn ($t) => \App\Models\TodoTask::bucketKey($t->deadline_date, $t->deadline_granularity) === \App\Models\TodoTask::BUCKET_TODAY)->count();
        $doneWeekCount = $completed->filter(fn ($t) => $t->completed_at && $t->completed_at->gte(now()->startOfWeek()))->count();
        $weekTotal = $openCount + $doneWeekCount;
        $weekPct = $weekTotal > 0 ? (int) round($doneWeekCount / $weekTotal * 100) : 0;
        $ringCircumference = 226.19;
        $ringOffset = $ringCircumference * (1 - $weekPct / 100);
        $linked = $active->filter(fn ($t) => filled($t->link_label))->take(4);
        $activity = $active->concat($completed)->sortByDesc('updated_at')->take(5);
    @endphp

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="myt" x-data="todoBoard()">
                <div class="myt-page">

                    {{-- Page head --}}
                    <div class="page-head">
                        <div>
                            <span class="eyebrow">{{ __('Personal workspace') }}</span>
                            <h1>{{ __('My Tasks') }}</h1>
                            <div class="head-sub">
                                <span><b>{{ $openCount }}</b> {{ __('open') }}</span>
                                @if($overdueCount > 0)
                                    <span class="sep"></span>
                                    <span class="warn"><b>{{ $overdueCount }}</b> {{ __('overdue') }}</span>
                                @endif
                                <span class="sep"></span>
                                <span>{{ __('Updated :time', ['time' => now()->format('M j, g:i A')]) }}</span>
                            </div>
                        </div>
                        <div class="head-actions">
                            <button type="button" class="btn btn-ghost" @click="chip = 'all'; tab = 'active'">
                                <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414v5.586a1 1 0 01-1.447.894l-2-1A1 1 0 019 18v-5.293L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                {{ __('Filters') }}
                            </button>
                            <button type="button" class="btn btn-cta" onclick="document.getElementById('quickAdd').focus()">
                                <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                {{ __('New Task') }}
                            </button>
                        </div>
                    </div>

                    {{-- Stat strip --}}
                    <div class="stats">
                        <div class="stat s-open">
                            <span class="tile"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></span>
                            <span><span class="n">{{ $openCount }}</span><span class="l">{{ __('Open') }}</span></span>
                        </div>
                        <div class="stat s-over">
                            <span class="tile"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                            <span><span class="n">{{ $overdueCount }}</span><span class="l">{{ __('Overdue') }}</span></span>
                        </div>
                        <div class="stat s-today">
                            <span class="tile"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                            <span><span class="n">{{ $todayCount }}</span><span class="l">{{ __('Due today') }}</span></span>
                        </div>
                        <div class="stat s-done">
                            <span class="tile"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                            <span><span class="n">{{ $doneWeekCount }}</span><span class="l">{{ __('Done this week') }}</span></span>
                        </div>
                    </div>

                    {{-- Command bar / quick add --}}
                    <form method="POST" action="{{ route('todo.store') }}" x-data="todoComposer()" @item-selected="onLinkSelected($event)" class="command">
                        @csrf
                        <span class="cmd-plus"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg></span>
                        <input
                            id="quickAdd"
                            type="text"
                            name="title"
                            required
                            maxlength="255"
                            autocomplete="off"
                            placeholder="{{ __('Add a task… (press Enter to add)') }}"
                        />
                        <kbd>&#9166; {{ __('Enter') }}</kbd>
                        <div class="cmd-div"></div>
                        <button type="button" class="cmd-btn" @click="open = !open" :class="open ? 'on' : ''">
                            <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ __('Date') }}
                        </button>
                        <button type="button" class="cmd-btn" @click="open = !open" :class="open ? 'on' : ''">
                            <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a2 2 0 011.414.586l5 5a2 2 0 010 2.828l-5 5A2 2 0 0112 17H7a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                            {{ __('Priority') }}
                        </button>
                        <button type="button" class="cmd-btn" @click="open = !open" :class="open ? 'on' : ''">
                            <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 015.656 0l3 3a4 4 0 01-5.657 5.657l-1.5-1.5M10.172 13.828a4 4 0 01-5.656 0l-3-3a4 4 0 015.657-5.657l1.5 1.5"/></svg>
                            {{ __('Link') }}
                        </button>
                        <button type="submit" class="cmd-add">{{ __('Add') }}</button>

                        <div x-show="open" x-collapse x-cloak class="command-details">
                            <div class="command-fields">
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
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            <x-input-error :messages="$errors->get('deadline_date')" class="mt-2" />
                        </div>
                    </form>

                    {{-- Toolbar: tabs + search --}}
                    <div class="toolbar">
                        <div class="myt-tabs">
                            <button type="button" class="myt-tab" @click="tab = 'active'" :class="tab === 'active' ? 'active' : ''">
                                {{ __('Active') }} <span class="tab-count">{{ $openCount }}</span>
                            </button>
                            <button type="button" class="myt-tab" @click="tab = 'completed'" :class="tab === 'completed' ? 'active' : ''">
                                {{ __('Completed') }} <span class="tab-count">{{ $completed->count() }}</span>
                            </button>
                        </div>
                        <div class="search">
                            <svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="q" placeholder="{{ __('Search tasks…') }}" />
                        </div>
                    </div>

                    {{-- Filter chips --}}
                    <div class="chips" x-show="tab === 'active'">
                        <button type="button" class="fchip" @click="chip = 'all'" :class="chip === 'all' ? 'on' : ''">{{ __('All') }}</button>
                        <button type="button" class="fchip" @click="chip = 'overdue'" :class="chip === 'overdue' ? 'on' : ''">{{ __('Overdue') }}</button>
                        <button type="button" class="fchip" @click="chip = 'today'" :class="chip === 'today' ? 'on' : ''">{{ __('Today') }}</button>
                        <button type="button" class="fchip" @click="chip = 'week'" :class="chip === 'week' ? 'on' : ''">{{ __('This week') }}</button>
                        <button type="button" class="fchip" @click="chip = 'none'" :class="chip === 'none' ? 'on' : ''">{{ __('No deadline') }}</button>
                    </div>

                    <div class="grid-x">
                        <div class="main-col">

                            {{-- ACTIVE view --}}
                            <div x-show="tab === 'active'">
                                @if($active->isEmpty())
                                    <div class="empty">
                                        <span class="halo"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
                                        <p class="t">{{ __('No active tasks') }}</p>
                                        <p class="s">{{ __('Add a task above to get started.') }}</p>
                                    </div>
                                @else
                                    @foreach($groups as $bucket => $tasks)
                                        @if($tasks->isEmpty())
                                            @continue
                                        @endif
                                        <section class="section" x-show="sectionHas($el)">
                                            <div class="section-head">
                                                <span class="section-title {{ $bucket === \App\Models\TodoTask::BUCKET_OVERDUE ? 'overdue' : '' }}">{{ $bucketLabels[$bucket] }}</span>
                                                <span class="section-count {{ $bucket === \App\Models\TodoTask::BUCKET_OVERDUE ? 'overdue' : '' }}">{{ $tasks->count() }}</span>
                                            </div>
                                            <div class="task-list">
                                                @foreach($tasks as $task)
                                                    @include('todo._task-row', ['task' => $task, 'bucket' => $bucket])
                                                @endforeach
                                            </div>
                                        </section>
                                    @endforeach

                                    <div class="empty" x-show="!anyMatch('active')" x-cloak>
                                        <span class="halo"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></span>
                                        <p class="t">{{ __('No tasks match your filters') }}</p>
                                        <p class="s">{{ __('Try a different search or filter.') }}</p>
                                    </div>
                                @endif
                            </div>

                            {{-- COMPLETED view --}}
                            <div x-show="tab === 'completed'" x-cloak>
                                @if($completed->isEmpty())
                                    <div class="empty">
                                        <span class="halo"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                        <p class="t">{{ __('Nothing completed yet') }}</p>
                                        <p class="s">{{ __('Completed tasks will appear here for review.') }}</p>
                                    </div>
                                @else
                                    <section class="section">
                                        <div class="section-head">
                                            <span class="section-title">{{ __('Completed') }}</span>
                                            <span class="section-count">{{ $completed->count() }}</span>
                                        </div>
                                        <div class="task-list">
                                            @foreach($completed as $task)
                                                @include('todo._task-row-completed', ['task' => $task])
                                            @endforeach
                                        </div>
                                    </section>

                                    <div class="empty" x-show="!anyMatch('completed')" x-cloak>
                                        <span class="halo"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></span>
                                        <p class="t">{{ __('No tasks match your search') }}</p>
                                        <p class="s">{{ __('Try a different search.') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Rail --}}
                        <aside class="rail-col">
                            <div class="progress-card">
                                <div class="pc-head">
                                    <span class="pc-title">{{ __('This week') }}</span>
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.5)" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div class="pc-body">
                                    <div class="ring">
                                        <svg width="88" height="88" viewBox="0 0 88 88">
                                            <circle class="track" cx="44" cy="44" r="36" fill="none" stroke-width="7"/>
                                            <circle class="bar" cx="44" cy="44" r="36" fill="none" stroke-width="7" stroke-dasharray="{{ $ringCircumference }}" stroke-dashoffset="{{ $ringOffset }}"/>
                                        </svg>
                                        <span class="val">{{ $weekPct }}%</span>
                                    </div>
                                    <div class="pc-meta">
                                        <div class="big">{{ trans_choice(':count task done|:count tasks done', $doneWeekCount, ['count' => $doneWeekCount]) }}</div>
                                        <div class="small">{{ __(':open still open', ['open' => $openCount]) }}</div>
                                    </div>
                                </div>
                                <div class="pc-chips">
                                    <span class="pc-chip">{{ $overdueCount }}<span>{{ __('Overdue') }}</span></span>
                                    <span class="pc-chip">{{ $todayCount }}<span>{{ __('Today') }}</span></span>
                                    <span class="pc-chip">{{ $doneWeekCount }}<span>{{ __('Done') }}</span></span>
                                </div>
                            </div>

                            <div class="rail-card">
                                <div class="rc-head">
                                    <span class="rc-title">{{ __('Linked records') }}</span>
                                </div>
                                @if($linked->isEmpty())
                                    <div class="empty" style="border:0;background:transparent;padding:24px 16px;box-shadow:none;">
                                        <p class="s">{{ __('No linked records yet.') }}</p>
                                    </div>
                                @else
                                    @foreach($linked as $task)
                                        <a href="{{ $task->link_url }}" target="_blank" rel="noopener" class="rec-row">
                                            <span class="rec-ic"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 015.656 0l3 3a4 4 0 01-5.657 5.657l-1.5-1.5M10.172 13.828a4 4 0 01-5.656 0l-3-3a4 4 0 015.657-5.657l1.5 1.5"/></svg></span>
                                            <span class="rec-main">
                                                <span class="rec-name">{{ $task->link_label }}</span>
                                                <span class="rec-type">{{ $task->linkable_type ? class_basename($task->linkable_type) : __('Record') }}</span>
                                            </span>
                                            <span class="rec-go"><svg fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
                                        </a>
                                    @endforeach
                                @endif
                            </div>

                            <div class="rail-card">
                                <div class="rc-head">
                                    <span class="rc-title">{{ __('Latest activity') }}</span>
                                </div>
                                <div class="act">
                                    @forelse($activity as $task)
                                        <div class="act-item {{ $task->isOverdue() ? 'red' : ($task->status === \App\Models\TodoTask::STATUS_COMPLETED ? 'green' : 'grey') }}">
                                            <span class="act-dot"></span>
                                            <div>
                                                <div class="act-text">
                                                    @if($task->status === \App\Models\TodoTask::STATUS_COMPLETED)
                                                        {{ __('Completed') }} <b>{{ $task->title }}</b>
                                                    @elseif($task->isOverdue())
                                                        <b>{{ $task->title }}</b> {{ __('is overdue') }}
                                                    @else
                                                        {{ __('Open') }} <b>{{ $task->title }}</b>
                                                    @endif
                                                </div>
                                                <div class="act-time">{{ $task->updated_at?->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="act-item grey">
                                            <span class="act-dot"></span>
                                            <div class="act-text">{{ __('No activity yet.') }}</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </aside>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
