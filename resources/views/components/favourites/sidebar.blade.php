@props(['favouriteMeta' => null, 'favouriteOverride' => false])

<aside class="fav-sidebar shrink-0"
       x-data="{ store: $store.favourites }"
       :class="{ 'visible': store.pinned, 'collapsed': store.collapsed }"
       @click="store.collapsed && store.expand()"
       x-cloak>
    <div class="fav-sidebar-head" x-show="store.pinned && !store.collapsed" x-cloak>
        <div class="fav-sidebar-head-row">
            <span class="fav-label">{{ __('Favs') }}</span>
            <button type="button" class="fav-unpin-btn" title="{{ __('Unpin from sidebar') }}" @click.stop="store.setPinned(false)">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 17v5M9 2h6l1 7 3 3v2H5v-2l3-3z"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="fav-nav-scroll" x-show="store.pinned" x-ref="list" x-cloak>
        {{-- System-pinned My Tasks --}}
        <div class="fav-item pinned-item"
             :class="{ 'current': store.currentKey === 'my-tasks' }"
             :title="'{{ __('My Tasks') }}' + ' (always pinned)'"
             @click.stop="store.handleItemClick({ page_key: 'my-tasks', url: '{{ route('todo.index') }}', label: '{{ __('My Tasks') }}' }, $event)">
            <span class="fav-page-icon" x-html="store.icon('list-check')"></span>
            <span class="fav-item-label" x-show="!store.collapsed">{{ __('My Tasks') }}</span>
        </div>

        <div class="fav-divider" x-show="store.items.length"></div>

        <template x-for="item in store.items" :key="item.page_key">
            <div class="fav-item"
                 :draggable="store.dragArmed === item.page_key"
                 :class="{ 'current': item.page_key === store.currentKey, 'armed': store.dragArmed === item.page_key }"
                 :title="item.label"
                 @click.stop="store.handleItemClick(item, $event)"
                 @mousedown="store.startHold(item.page_key)"
                 @mouseup="store.endHold(item.page_key)"
                 @mouseleave="store.cancelHold()"
                 @dragstart="store.dragStart(item.page_key, $event)"
                 @dragend="store.dragEnd()"
                 @dragover.prevent="store.dragOver(item.page_key, $event)"
                 @drop.prevent="store.drop(item.page_key, $event)">
                <span class="fav-page-icon" x-html="store.icon(item.icon)"></span>
                <span class="fav-item-label" x-show="!store.collapsed" x-text="item.label"></span>
            </div>
        </template>
    </div>

    @if($favouriteMeta && !$favouriteOverride)
        <div class="fav-sidebar-toggle" x-show="store.pinned" x-cloak>
            <x-favourite-toggle :page-key="$favouriteMeta['key']" :label="$favouriteMeta['label']" :icon="$favouriteMeta['icon']" :url="$favouriteMeta['url']" />
        </div>
    @endif

    <button type="button" class="fav-collapse-btn"
            :class="{ 'flipped': store.collapsed }"
            :title="store.collapsed ? '{{ __('Expand sidebar') }}' : '{{ __('Collapse sidebar') }}'"
            @click.stop="store.toggleCollapse()"
            x-show="store.pinned"
            x-cloak>
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18l-6-6 6-6"/>
        </svg>
    </button>
</aside>
