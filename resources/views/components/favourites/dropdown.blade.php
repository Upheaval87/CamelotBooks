<div class="relative fav-dropdown-wrap"
     x-data="{ store: $store.favourites }"
     @keydown.escape.window="store.dropdownOpen = false">
    <button type="button"
            class="fav-star-trigger"
            :class="{ 'active': store.dropdownOpen }"
            @click="store.toggleDropdown()"
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
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-0.5"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-0.5"
         @click.outside="store.dropdownOpen = false"
         class="fav-star-dropdown"
         x-cloak>

        {{-- Favourites grid --}}
        <template x-if="!store.pickerOpen">
            <div>
                <div class="fav-star-dropdown-head">
                    <span>{{ __('Favourites') }}</span>
                    <div class="fav-star-dropdown-actions">
                        <button type="button" class="fav-pin-toggle" :class="{ 'pinned': store.pinned }" @click="store.setPinned(!store.pinned)">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 17v5M9 2h6l1 7 3 3v2H5v-2l3-3z"/>
                            </svg>
                            <span x-text="store.pinned ? '{{ __('Unpin from sidebar') }}' : '{{ __('Pin to sidebar') }}'"></span>
                        </button>
                        <button type="button" class="fav-manage-link" @click="store.openPicker()">{{ __('Add') }}</button>
                    </div>
                </div>
                <p class="fav-star-dropdown-sub" x-text="store.pinHint"></p>

                <div class="fav-star-grid" x-show="store.visibleItems.length">
                    <template x-for="item in store.visibleItems" :key="item.page_key">
                        <div class="fav-star-tile"
                             :class="{ 'current': item.page_key === store.currentKey }">
                            <button type="button" class="fav-star-tile-main" @click="store.go(item)" @click.right.prevent="store.remove(item.page_key)">
                                <span class="fav-star-tile-ic" x-html="store.icon(item.icon)"></span>
                                <span class="fav-star-tile-lbl" x-text="item.label"></span>
                            </button>
                            <button type="button" class="fav-star-tile-remove" :title="'{{ __('Remove') }}: ' + item.label" @click.stop="store.remove(item.page_key)">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M18 6L6 18M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div class="fav-star-empty" x-show="!store.visibleItems.length">
                    <p>{{ __('No favourites yet.') }}</p>
                    <p class="fav-star-empty-sub">{{ __('Star a page from its header, or pick one below.') }}</p>
                </div>
            </div>
        </template>

        {{-- Page picker --}}
        <template x-if="store.pickerOpen">
            <div>
                <div class="fav-star-dropdown-head">
                    <span>{{ __('Add a favourite') }}</span>
                    <div class="fav-star-dropdown-actions">
                        <button type="button" class="fav-manage-link" @click="store.pickerOpen = false">{{ __('Back') }}</button>
                    </div>
                </div>
                <input type="search"
                       class="fav-picker-search"
                       placeholder="{{ __('Search pages…') }}"
                       x-model="store.pickerQuery">
                <div class="fav-picker-list" x-show="store.filteredPages.length">
                    <template x-for="page in store.filteredPages" :key="page.page_key">
                        <button type="button"
                                class="fav-picker-item"
                                :class="{ 'added': store.isFav(page.page_key) }"
                                @click="store.pick(page)">
                            <span class="fav-picker-ic" x-html="store.icon(page.icon)"></span>
                            <span class="fav-picker-lbl" x-text="page.label"></span>
                            <svg class="fav-picker-check" x-show="store.isFav(page.page_key)" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg class="fav-picker-plus" x-show="!store.isFav(page.page_key)" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                            </svg>
                        </button>
                    </template>
                </div>
                <p class="fav-star-empty" x-show="!store.filteredPages.length">{{ __('No pages match.') }}</p>
            </div>
        </template>
    </div>
</div>
