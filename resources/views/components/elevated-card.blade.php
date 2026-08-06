@props(['padding' => true])

@php
    $classes = $padding ? 'elevated-card elevated-card--padded' : 'elevated-card elevated-card--flush';
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
