@props(['variant' => 'ghost', 'href' => null, 'disabled' => false, 'title' => null])

@if($href)
    <x-button :variant="$variant" :href="$href" :disabled="$disabled" :title="$title" {{ $attributes }}>
        {{ $slot }}
    </x-button>
@else
    <x-button :variant="$variant" :disabled="$disabled" :title="$title" {{ $attributes }}>
        {{ $slot }}
    </x-button>
@endif
