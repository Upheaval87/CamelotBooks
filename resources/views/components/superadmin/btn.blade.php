@props(['variant' => 'primary', 'size' => 'lg', 'href' => null, 'type' => 'button', 'disabled' => false])

<x-ui.btn :variant="$variant" :size="$size" :href="$href" :type="$type" :disabled="$disabled" {{ $attributes }}>
    {{ $slot }}
</x-ui.btn>
