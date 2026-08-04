<aside class="fav-sidebar"
       x-data="{ store: $store.favourites }"
       :class="{ 'visible': store.pinned, 'collapsed': store.collapsed }"
       @click="store.collapsed && store.expand()"
       x-cloak>
    <div class="fav-sidebar-head">
        <div class="fav-sidebar-head-row">
            <span class="fav-label" x-show="!store.collapsed">{{ __('Favourites') }}</span>
            <button type="button" class="fav-unpin-btn" title="{{ __('Unpin from sidebar') }}" @click.stop="store.setPinned(false)">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 17v5M9 2h6l1 7 3 3v2H5v-2l3-3z"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="fav-nav-scroll" x-ref="list">
        {{-- System-pinned My Tasks --}}
        <div class="fav-item pinned-item"
             :class="{ 'current': store.currentKey === 'my-tasks' }"
             :title="'{{ __('My Tasks') }}' + ' (always pinned)'"
             @click="store.handleItemClick({ page_key: 'my-tasks', url: '{{ route('todo.index') }}', label: '{{ __('My Tasks') }}' }, $event)">
            <span class="fav-page-icon" x-html="store.icon('list-check')"></span>
            <span class="fav-item-label" x-show="!store.collapsed">{{ __('My Tasks') }}</span>
        </div>

        <div class="fav-divider" x-show="store.items.length"></div>

        <template x-for="item in store.items" :key="item.page_key">
            <div class="fav-item"
                 :draggable="store.dragArmed === item.page_key"
                 :class="{ 'current': item.page_key === store.currentKey, 'armed': store.dragArmed === item.page_key }"
                 :title="item.label"
                 @click="store.handleItemClick(item, $event)"
                 @mousedown="store.startHold(item.page_key)"
                 @mouseup="store.endHold(item.page_key)"
                 @mouseleave="store.cancelHold()"
                 @dragstart="store.dragStart(item.page_key, $event)"
                 @dragend="store.dragEnd()"
                 @dragover.prevent="store.dragOver(item.page_key, $event)"
                 @drop.prevent="store.drop(item.page_key, $event)">
                <button type="button" class="fav-remove" :title="store.dragArmed === item.page_key ? '{{ __('Drag to reorder') }}' : '{{ __('Remove') }}'" @click.stop="store.dragArmed === item.page_key ? null : store.remove(item.page_key)">
                    <svg x-show="store.dragArmed === item.page_key" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 9h8M8 15h8M8 5h0M16 5h0M8 19h0M16 19h0"/>
                    </svg>
                    <svg x-show="store.dragArmed !== item.page_key" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
                <span class="fav-page-icon" x-html="store.icon(item.icon)"></span>
                <span class="fav-item-label" x-show="!store.collapsed" x-text="item.label"></span>
            </div>
        </template>
    </div>

    <button type="button" class="fav-collapse-btn"
            :title="store.collapsed ? '{{ __('Expand sidebar') }}' : '{{ __('Collapse sidebar') }}'"
            @click.stop="store.toggleCollapse()">
        <svg x-show="store.collapsed" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/>
        </svg>
        <svg x-show="!store.collapsed" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18l-6-6 6-6"/>
        </svg>
    </button>
</aside>
