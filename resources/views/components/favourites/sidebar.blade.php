@props(['favouriteMeta' => null, 'favouriteOverride' => false])

<aside class="fav-sidebar shrink-0"
       x-data="{ store: $store.favourites }"
       :class="{ 'visible': store.pinned, 'collapsed': store.collapsed }"
       @click="store.collapsed && store.expand()"
       x-cloak>
    <div class="fav-sidebar-head" x-show="store.pinned" x-cloak>
        <div class="fav-sidebar-head-row">
            <span class="fav-label">{{ __('Favs') }}</span>
            <button type="button" class="fav-pin-btn"
                    title="{{ __('Unpin from sidebar') }}"
                    aria-label="{{ __('Unpin from sidebar') }}"
                    @click.stop="store.setPinned(false)">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2v6m0 0l3-3m-3 3l-3-3m3 12v4m-4-4h8"/></svg>
            </button>
        </div>
    </div>

    <nav class="fav-nav-scroll" x-show="store.pinned" x-ref="list" x-cloak>
        {{-- System-pinned My Tasks --}}
        <div class="fav-item pinned-item"
             :class="{ 'current': store.currentKey === 'my-tasks' }"
             :title="store.collapsed ? false : @js(__('My Tasks') . ' (always pinned)')"
             @mouseenter="store.showTip($event, @js(__('My Tasks')))"
             @mouseleave="store.hideTip()"
             @click.stop="store.handleItemClick({ page_key: 'my-tasks', url: @js(route('todo.index', absolute: false)), label: @js(__('My Tasks')) }, $event)">
            <span class="fav-page-icon" x-html="store.icon('list-check')"></span>
            <span class="fav-item-label">{{ __('My Tasks') }}</span>
        </div>

        <div class="fav-divider" x-show="store.items.length"></div>

        <template x-for="item in store.items" :key="item.page_key">
            <div class="fav-item"
                 :draggable="store.dragArmed === item.page_key"
                 :class="{ 'current': item.page_key === store.currentKey, 'armed': store.dragArmed === item.page_key }"
                 :title="store.collapsed ? false : item.label"
                 @mouseenter="store.showTip($event, item.label)"
                 @mouseleave.stop="store.hideTip(); store.cancelHold()"
                 @click.stop="store.handleItemClick(item, $event)"
                 @mousedown="store.startHold(item.page_key)"
                 @mouseup="store.endHold(item.page_key)"
                 @dragstart="store.dragStart(item.page_key, $event)"
                 @dragend="store.dragEnd()"
                 @dragover.prevent="store.dragOver(item.page_key, $event)"
                 @drop.prevent="store.drop(item.page_key, $event)">
                <span class="fav-page-icon" x-html="store.icon(item.icon)"></span>
                <span class="fav-item-label" x-text="item.label"></span>
            </div>
        </template>
    </nav>

    <div class="fav-sidebar-foot" x-show="store.pinned" x-cloak>
        @if($favouriteMeta && !$favouriteOverride)
            <div class="fav-sidebar-toggle">
                <x-favourite-toggle :page-key="$favouriteMeta['key']" :label="$favouriteMeta['label']" :icon="$favouriteMeta['icon']" :url="$favouriteMeta['url']" />
            </div>
        @endif

        <button type="button" class="fav-collapse-btn"
                :title="store.collapsed ? @js(__('Expand to stacked')) : @js(__('Collapse to icons'))"
                :aria-label="store.collapsed ? @js(__('Expand to stacked')) : @js(__('Collapse to icons'))"
                @click.stop="store.toggleCollapse()">
            <svg viewBox="0 0 24 24" x-show="!store.collapsed" aria-hidden="true"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"/></svg>
            <svg viewBox="0 0 24 24" x-show="store.collapsed" x-cloak aria-hidden="true"><path d="M13 17l5-5-5-5M6 17l5-5-5-5"/></svg>
        </button>
    </div>

    {{-- Collapsed-state tooltip. Lives outside .fav-nav-scroll so its overflow cannot clip it. --}}
    <div class="fav-tip"
         x-show="store.pinned"
         :class="{ 'is-open': store.tip }"
         :style="`top: ${store.tipTop}px`"
         x-text="store.tipLabel"
         aria-hidden="true"
         x-cloak></div>
</aside>
