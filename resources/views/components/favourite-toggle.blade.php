@props([
    'pageKey' => '',
    'label' => '',
    'icon' => 'star',
    'url' => '',
    'locked' => false,
])

<button type="button"
        x-data="favouriteToggle(@js($pageKey), @js($label), @js($icon), @js($url), @js($locked))"
        x-init="init()"
        @click="toggle()"
        class="favourite-toggle"
        :class="{ 'active': isFav, 'locked': locked }"
        :title="title"
        :aria-pressed="isFav ? 'true' : 'false'"
        aria-label="{{ $locked ? __('This page is always pinned') : __('Favourite this page') }}">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
              :class="isFav ? 'fill-current' : 'fill-none'"
              d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/>
    </svg>
</button>
