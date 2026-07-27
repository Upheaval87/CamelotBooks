@props([
    'name',
    'items' => [],
    'valueKey' => 'id',
    'labelKey' => 'name',
    'searchKeys' => ['name'],
    'showFields' => [],
    'placeholder' => 'Search...',
    'preload' => null,
    'preloadLabel' => null,
    'mode' => 'client',
    'searchUrl' => null,
    'onSelect' => null,
    'enableAdvancedSearch' => false,
    'advancedSearchName' => null,
    'required' => false,
    'disabled' => false,
    'barcodeAutoSelect' => true,
])

@php
    $safeName = str_replace(['[', ']'], '_', $name);
    $serializedItems = $items->values()->toJson();
@endphp

<div
    x-data="searchableSelect({
        name: '{{ $name }}',
        items: {{ $serializedItems }},
        valueKey: '{{ $valueKey }}',
        labelKey: '{{ $labelKey }}',
        searchKeys: {{ json_encode($searchKeys) }},
        showFields: {{ json_encode($showFields) }},
        preload: @js($preload),
        preloadLabel: @js($preloadLabel),
        mode: '{{ $mode }}',
        searchUrl: {{ $searchUrl ? json_encode($searchUrl) : 'null' }},
        onSelectCallback: @js($onSelect),
        enableAdvancedSearch: @js($enableAdvancedSearch),
        advancedSearchName: @js($advancedSearchName),
        barcodeAutoSelect: @js($barcodeAutoSelect),
    })"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="selectedId" {{ $required ? 'required' }} />
    <div class="flex">
        <input
            type="text"
            x-model="query"
            @input.debounce.200ms="filter()"
            @focus="if(query.length > 0) open = true"
            @keydown.down.prevent="moveHighlight(1)"
            @keydown.up.prevent="moveHighlight(-1)"
            @keydown.enter.prevent="confirmHighlight()"
            @keydown.escape="open = false"
            @keydown.tab="open = false"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            {{ $disabled ? 'disabled' : '' }}
            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-l-md shadow-sm text-sm"
        />
        @if($enableAdvancedSearch)
            <button
                type="button"
                @click="openAdvancedSearch()"
                class="px-2.5 bg-gray-50 border border-l-0 border-gray-300 rounded-r-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-colors"
                title="Advanced Search"
            >
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        @else
            <span class="inline-flex items-center px-2.5 bg-gray-50 border border-l-0 border-gray-300 rounded-r-md">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
        @endif
    </div>
</div>
