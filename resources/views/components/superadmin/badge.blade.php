@props(['variant' => 'muted'])

<x-ui.badge :variant="$variant" {{ $attributes }}>
    {{ $slot }}
</x-ui.badge>
