@props(['variant' => 'navy', 'dot' => false])

@php
$baseClass = $dot ? 'badge-dot' : 'badge';
$variantClasses = match($variant) {
    'navy' => 'badge-navy',
    'navy-outline' => 'badge-navy-outline',
    'amber-16' => 'badge-amber-16',
    'amber-24' => 'badge-amber-24',
    'amber-32' => 'badge-amber-32',
    'amber' => 'badge-amber-solid',
    'blue' => 'badge-blue',
    'danger' => 'badge-danger',
    default => 'badge-navy',
};
$dotClass = $dot ? 'badge-dot-' . ($variant === 'amber' || $variant === 'amber-16' || $variant === 'amber-24' || $variant === 'amber-32' ? 'amber' : ($variant === 'blue' ? 'blue' : ($variant === 'danger' ? 'danger' : 'navy'))) : '';
@endphp

<span {{ $attributes->merge(['class' => "$baseClass $variantClasses $dotClass"]) }}>
    {{ $slot }}
</span>
