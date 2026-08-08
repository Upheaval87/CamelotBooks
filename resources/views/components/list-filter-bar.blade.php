@props(['searchRoute' => '', 'searchPlaceholder' => '', 'entity' => '', 'countText' => ''])

<form action="{{ $searchRoute }}" method="GET" class="list-filter-bar">
    @if($searchRoute)
    <div class="list-filter-search">
        <div class="scoped-search-field">
            <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $searchPlaceholder }}" autocomplete="off" />
            <span class="scoped-search-divider" aria-hidden="true"></span>
            <button type="button"
                    class="scoped-search-open"
                    title="{{ $entity ? __('Search this list') : __('Search across all records') }}"
                    onclick="window.dispatchEvent(new CustomEvent('open-global-search', { detail: { entity: '{{ $entity }}' } }))">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    {{ $slot }}

    @if(request()->has('search') || request()->has('status') || request()->has('type') || request()->has('department') || request()->has('terms'))
    <a href="{{ url()->current() }}" class="list-filter-clear">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        Clear
    </a>
    @endif

    @if($countText)
    <span class="list-filter-count">{{ $countText }}</span>
    @endif
</form>
