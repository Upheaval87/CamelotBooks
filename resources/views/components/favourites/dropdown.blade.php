<div class="relative fav-dropdown-wrap"
     x-data="{ store: $store.favourites }"
     @keydown.escape.window="store.dropdownOpen = false">
    <button type="button"
            class="fav-star-trigger"
            :class="{ 'active': store.dropdownOpen }"
            @click.stop="store.toggleDropdown()"
            title="{{ __('Favourites') }}"
            aria-haspopup="true"
            :aria-expanded="store.dropdownOpen ? 'true' : 'false'">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/>
        </svg>
        <span class="hidden lg:inline">{{ __('Favourites') }}</span>
        <span class="fav-count" x-text="store.count()"></span>
    </button>

    <div x-show="store.dropdownOpen"
         @click.outside="store.dropdownOpen = false"
         class="favpop"
         x-cloak>

        {{-- Head --}}
        <div class="favpop-head">
            <span class="favpop-ic">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>
            </span>
            <span class="favpop-title">{{ __('Favourite pages') }}</span>
            <span class="favpop-pill" x-text="store.pinCount + ' {{ __('pinned') }}'"></span>
            <button type="button" class="favpop-close" @click="store.dropdownOpen = false" title="{{ __('Close') }}" aria-label="{{ __('Close') }}">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Controls: rail master switch + pin current page --}}
        <div class="favpop-controls">
            <div class="favpop-switch"
                 role="switch"
                 tabindex="0"
                 :aria-checked="store.pinned ? 'true' : 'false'"
                 @click="store.setPinned(!store.pinned)"
                 @keydown.enter.prevent="store.setPinned(!store.pinned)"
                 @keydown.space.prevent="store.setPinned(!store.pinned)">
                <span class="favpop-sw" :class="{ 'on': store.pinned }"></span>
                <span style="display:flex;flex-direction:column;line-height:1.15">
                    <span class="lbl">{{ __('Show rail on desktop') }}</span>
                    <span class="sub">{{ __('Pin favourites to the left edge') }}</span>
                </span>
            </div>
            <button type="button" class="favpop-btn-mini"
                    x-show="store.currentKey" x-cloak
                    @click="store.toggle(store.currentKey, store.currentLabel, store.currentIcon, store.currentUrl)">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span x-text="store.isFav(store.currentKey) ? '{{ __('Unpin this page') }}' : '{{ __('Pin this page') }}'"></span>
            </button>
        </div>

        {{-- Body --}}
        <div class="favpop-body">
            <p class="favpop-hint">{{ __('Star any page to keep it one click away. Pinned pages appear on the left rail while you work.') }}</p>

            <div class="favpop-search">
                <svg fill="none" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4.3-4.3"/></svg>
                <input type="search" placeholder="{{ __('Filter pages…') }}" x-model="store.filter" aria-label="{{ __('Filter pages') }}">
            </div>

            <div class="favpop-sec">
                <span class="t">{{ __('Pinned to your rail') }}</span>
                <span class="c" x-text="store.pinCount"></span>
            </div>
            <div class="favpop-grid">
                <template x-for="item in store.pinnedItems" :key="item.page_key">
                    <button type="button" class="favpop-tile pinned"
                            :title="item.page_key === 'my-tasks' ? '{{ __('Always pinned') }}' : '{{ __('Unpin') }}'"
                            @click="item.page_key === 'my-tasks' ? store.go(item) : store.remove(item.page_key)">
                        <span class="favpop-tic" x-html="store.icon(item.icon)"></span>
                        <span class="favpop-tl" x-text="item.label"></span>
                        <span class="favpop-badge chk" aria-hidden="true"><svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                        <span class="favpop-badge rm" x-show="item.page_key !== 'my-tasks'" aria-hidden="true"><svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg></span>
                    </button>
                </template>
            </div>

            <div class="favpop-sec muted">
                <span class="t">{{ __('Available to pin') }}</span>
                <span class="c" x-text="store.availCount"></span>
            </div>
            <div class="favpop-grid">
                <template x-for="page in store.availableItems" :key="page.page_key">
                    <button type="button" class="favpop-tile"
                            @click="store.add({ page_key: page.page_key, label: page.label, icon: page.icon, url: page.url })">
                        <span class="favpop-tic" x-html="store.icon(page.icon)"></span>
                        <span class="favpop-tl" x-text="page.label"></span>
                        <span class="favpop-badge add" aria-hidden="true"><svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg></span>
                    </button>
                </template>
            </div>
            <p class="favpop-empty" x-show="store.pagesLoaded && !store.availCount" x-cloak>{{ __('No more pages to pin.') }}</p>
        </div>

        {{-- Foot --}}
        <div class="favpop-foot">
            <button type="button" class="favpop-foot-link" @click="store.unpinAllWithConfirm()">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6"/></svg>
                {{ __('Unpin all from sidebar') }}
            </button>
            <button type="button" class="favpop-done" @click="store.dropdownOpen = false">{{ __('Done') }}</button>
        </div>
    </div>
</div>
