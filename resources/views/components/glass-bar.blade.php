@props(['variant' => 'top'])

<div {{ $attributes->merge(['class' => 'sa-glass sa-glass--' . $variant]) }}>
    {{ $slot }}
</div>
