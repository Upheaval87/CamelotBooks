<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="va-suite"
         x-data="reportCenter({
            groups: {{ Js::from($groups) }},
            favorites: {{ Js::from($favorites) }},
            initialSearch: {{ Js::from($initialSearch) }},
            initialCategory: {{ Js::from($initialCategory) }},
            toggleUrl: {{ Js::from(route('accounting.report-center.toggle-favorite', ['key' => ':key'])) }},
         })">
        <header class="va-head">
            <div class="va-head-text">
                <h1 class="va-title">Report Center</h1>
                <p class="va-sub">Browse and open every available report across your company.</p>
            </div>
            <div class="va-head-total">
                <span class="va-total-num" x-text="totalCount"></span>
                <span class="va-total-lbl">reports</span>
            </div>
        </header>

        <div class="va-shell">
            <aside class="va-side">
                <div class="va-search">
                    <svg class="va-search-ic" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input type="search" x-ref="search" x-model="q" class="va-search-input"
                           placeholder="Search reports" aria-label="Search reports" />
                    <button type="button" class="va-search-clear" x-show="q" @click="q = ''" aria-label="Clear search">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <nav class="va-nav" aria-label="Report categories">
                    <button type="button" class="va-nav-item" :class="{ 'is-active': cat === '' }" @click="cat = ''">
                        <svg class="va-nav-ic" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                        </svg>
                        <span class="va-nav-lbl">All Reports</span>
                        <span class="va-count" x-text="totalCount"></span>
                    </button>
                    <template x-for="c in cats" :key="c.key">
                        <button type="button" class="va-nav-item" :class="{ 'is-active': cat === c.key }" @click="cat = c.key">
                            <svg class="va-nav-ic" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path :d="c.icon"/>
                            </svg>
                            <span class="va-nav-lbl" x-text="c.label"></span>
                            <span class="va-count" x-text="c.count"></span>
                        </button>
                    </template>
                </nav>

                <div class="va-shelf" x-show="favs.length" x-cloak>
                    <div class="va-shelf-title">
                        <svg class="va-shelf-ic" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        <span>Favourites</span>
                    </div>
                    <template x-for="f in favs" :key="f.key">
                        <a class="va-fav" :href="f.url">
                            <svg class="va-fav-ic" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                            <span class="va-fav-name" x-text="f.name"></span>
                        </a>
                    </template>
                </div>
            </aside>

            <section class="va-main">
                <div class="va-toolbar">
                    <span class="va-tool-count">
                        <strong x-text="visibleCount"></strong> of <strong x-text="totalCount"></strong> reports
                    </span>
                    <label class="va-sort">
                        <span class="va-sort-lbl">Sort</span>
                        <select class="va-sort-select" x-model="sort" aria-label="Sort reports">
                            <option value="az">Name A–Z</option>
                            <option value="za">Name Z–A</option>
                        </select>
                    </label>
                </div>

                <div class="va-list">
                    <template x-for="g in visibleGroups" :key="g.key">
                        <section class="va-group">
                            <div class="va-group-head">
                                <h2 class="va-group-title" x-text="g.label"></h2>
                                <span class="va-group-count" x-text="g.reports.length"></span>
                            </div>
                            <div class="va-rows">
                                <template x-for="r in g.reports" :key="r.key">
                                <a class="va-row" :href="r.url">
                                    <span class="va-tile" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                            <path :d="r.icon"/>
                                        </svg>
                                    </span>
                                    <span class="va-txt">
                                        <span class="va-t" x-text="r.name"></span>
                                        <span class="va-d" x-text="r.description"></span>
                                        <span class="va-fmts">
                                            <template x-for="f in r.formats" :key="f">
                                                <span class="va-fmt" x-text="f"></span>
                                            </template>
                                        </span>
                                    </span>
                                    <button type="button" class="va-star" :class="{ 'is-fav': isFav(r.key) }"
                                            :aria-pressed="isFav(r.key) ? 'true' : 'false'"
                                            :aria-label="(isFav(r.key) ? 'Remove from favourites: ' : 'Add to favourites: ') + r.name"
                                            @click.prevent.stop="toggleFav(r)">
                                        <svg class="va-star-ic" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                    </button>
                                    <svg class="va-chev" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                    </svg>
                                </a>
                                </template>
                            </div>
                        </section>
                    </template>
                </div>

                <div class="va-empty" x-show="visibleGroups.length === 0" x-cloak>
                    <svg class="va-empty-ic" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="va-empty-text">No reports match your search.</p>
                    <button type="button" class="va-empty-clear" @click="clearFilters">Clear search &amp; filters</button>
                </div>
            </section>
        </div>
    </div>
</div>
</x-app-layout>
