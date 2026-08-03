@props(['searchUrl'])

@if($searchUrl)
<div x-data="globalSearchModal({{ Js::from(['searchUrl' => $searchUrl]) }})">
    <template x-teleport="body">
        <div x-show="open"
             x-cloak
             class="global-search-backdrop"
             @click.self="close()"
             role="dialog"
             aria-modal="true"
             aria-label="{{ __('Global Search') }}">
            <div class="global-search-panel">
                <div class="global-search-accent"></div>

                <div class="global-search-header">
                    <div>
                        <p class="global-search-eyebrow">{{ __('Global Search') }}</p>
                        <h2 class="global-search-title" x-text="entity ? ('Search ' + entityLabel()) : '{{ __('Search across all records') }}'"></h2>
                    </div>
                    <button type="button"
                            class="global-search-close"
                            @click="close()"
                            title="{{ __('Close (Esc)') }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="global-search-input-wrap">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input x-ref="input"
                           type="text"
                           x-model="query"
                           @input.debounce.250ms="doSearch()"
                           @keydown.down.prevent="moveHighlight(1)"
                           @keydown.up.prevent="moveHighlight(-1)"
                           @keydown.enter.prevent="confirmHighlight()"
                           placeholder="{{ __('Search customers, products, accounts...') }}"
                           autocomplete="off"
                           spellcheck="false" />
                    <button type="button"
                            x-show="entity"
                            class="global-search-scope"
                            @click="clearScope()"
                            title="{{ __('Search all records') }}">
                        <span x-text="entityLabel()"></span>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <kbd class="global-search-kbd">{{ __('Ctrl K') }}</kbd>
                </div>

                <div class="global-search-body" x-ref="list">
                    <div x-show="loading" class="global-search-empty">{{ __('Searching...') }}</div>

                    <div x-show="error && !loading" class="global-search-empty">{{ __('Search is unavailable right now.') }}</div>

                    <div x-show="!loading && !error && query.trim() === ''"
                         class="global-search-empty"
                         x-text="entity ? ('Type to search ' + entityLabel() + '...') : '{{ __('Type to search across customers, products, accounts and more.') }}'"></div>

                    <div x-show="!loading && !error && query.trim() !== '' && groups.length === 0"
                         class="global-search-empty">{{ __('No matches for') }} &ldquo;<span x-text="query"></span>&rdquo;</div>

                    <template x-for="(group, gi) in groups" :key="group.key">
                        <div class="global-search-group">
                            <p class="global-search-group-label">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="iconPath(group.icon)"/>
                                </svg>
                                <span x-text="group.label"></span>
                            </p>
                            <template x-for="(row, ri) in group.results" :key="group.key + '-' + ri">
                                <button type="button"
                                        class="global-search-row"
                                        :class="flatIndexOf(gi, ri) === highlightIndex ? 'is-highlighted' : ''"
                                        :data-i="flatIndexOf(gi, ri)"
                                        @click="selectResult(row)"
                                        @mouseenter="highlightIndex = flatIndexOf(gi, ri)">
                                    <span class="global-search-row-icon">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="iconPath(row.icon)"/>
                                        </svg>
                                    </span>
                                    <span class="global-search-row-text">
                                        <span class="global-search-row-title" x-text="row.title"></span>
                                        <span class="global-search-row-sub" x-show="row.subtitle" x-text="row.subtitle"></span>
                                    </span>
                                    <svg class="global-search-row-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="global-search-footer">
                    <span><kbd>&uarr;</kbd><kbd>&darr;</kbd>{{ __('navigate') }}</span>
                    <span><kbd>&crarr;</kbd>{{ __('open') }}</span>
                    <span><kbd>esc</kbd>{{ __('close') }}</span>
                    <span class="ml-auto">{{ __('Ctrl + K opens anywhere') }}</span>
                </div>
            </div>
        </div>
    </template>
</div>
@endif
