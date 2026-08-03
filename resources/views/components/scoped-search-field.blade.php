@props([
    'name',
    'entity',
    'searchUrl' => null,
    'value' => '',
    'label' => '',
    'placeholder' => 'Search...',
    'secondary' => [],
    'required' => false,
    'disabled' => false,
    'mode' => 'server',
    'items' => [],
    'valueKey' => 'id',
    'labelKey' => 'label',
    'onSelect' => null,
])

<div
    x-data="scopedSearchField({
        name: '{{ $name }}',
        entity: '{{ $entity }}',
        searchUrl: {{ $searchUrl ? json_encode($searchUrl) : 'null' }},
        value: @js($value),
        label: @js($label),
        mode: '{{ $mode }}',
        items: {{ $mode === 'client' ? $items->values()->toJson() : '[]' }},
        secondary: {{ json_encode($secondary) }},
        valueKey: '{{ $valueKey }}',
        labelKey: '{{ $labelKey }}',
        required: @js($required),
        disabled: @js($disabled),
        onSelect: @js($onSelect),
    })"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="selectedId" {{ $required ? 'required' : '' }} />

    <div class="scoped-search-field" :class="disabled ? 'is-disabled' : ''">
        <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>

        <input
            type="text"
            x-model="query"
            @input.debounce.200ms="filter()"
            @focus="if (query.length > 0) open = true"
            @keydown.down.prevent="moveHighlight(1)"
            @keydown.up.prevent="moveHighlight(-1)"
            @keydown.enter.prevent="confirmHighlight()"
            @keydown.escape="open = false"
            @keydown.tab="open = false"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            :disabled="disabled"
        />

        <span class="scoped-search-divider" aria-hidden="true"></span>

        <button
            type="button"
            class="scoped-search-open"
            :disabled="disabled || !entity"
            title="Search"
            @click="openGlobalSearch()"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>
    </div>

    <div x-show="open" x-cloak class="scoped-search-dropdown">
        <template x-for="(item, idx) in results" :key="item[valueKey]">
            <div
                @click="select(item)"
                @mouseenter="highlightIndex = parseInt(idx)"
                class="scoped-search-option"
                :class="parseInt(idx) === highlightIndex ? 'is-highlighted' : ''"
            >
                <span class="scoped-search-option-label" x-text="item[labelKey]"></span>
                <span class="scoped-search-option-sub" x-show="rowSubtitle(item)" x-text="rowSubtitle(item)"></span>
            </div>
        </template>
        <div x-show="loading" class="scoped-search-empty">Searching&hellip;</div>
        <div x-show="!loading && results.length === 0" class="scoped-search-empty">No matches found</div>
    </div>
</div>
