@props(['variant' => 'default', 'dot' => false])

@php
$variantClasses = match($variant) {
    'default', 'navy' => 'badge-default',
    'accent', 'blue' => 'badge-accent',
    'success', 'green' => 'badge-success',
    'warning', 'amber' => 'badge-warning',
    'danger', 'red' => 'badge-danger',
    'info' => 'badge-info',
    default => 'badge-default',
};
@endphp

<span {{ $attributes->merge(['class' => $variantClasses]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full bg-current opacity-60"></span>
    @endif
    {{ $slot }}
</span>
