@props(['variant' => 'neutral', 'dot' => false])

@php
    $variants = [
        'pending' => 'border-gold-500/40 bg-gold-500/[.12] text-gold-700',
        'approved' => 'border-green-600/30 bg-gradient-to-b from-mint-100 to-mint-200 text-green-700',
        'rejected' => 'border-red-300/60 bg-gradient-to-b from-red-50 to-red-100 text-red-700',
        'neutral' => 'border-gray-200 bg-gradient-to-b from-gray-50 to-gray-100 text-gray-500',
        'accent' => 'border-gold-600/30 bg-gradient-to-b from-[#F4FBFB] to-[#DFF7F6] text-gold-700',
        'navy' => 'border-navy-700/[.22] bg-navy-700/[.08] text-navy-700',
    ];
    $variantClass = $variants[$variant] ?? $variants['neutral'];
    $dotColor = $variant === 'approved'
        ? 'bg-green-500 shadow-[0_0_0_3px_rgba(34,197,94,.18)]'
        : ($variant === 'rejected'
            ? 'bg-red-500 shadow-[0_0_0_3px_rgba(239,68,68,.18)]'
            : 'bg-gold-500 shadow-[0_0_0_3px_rgba(18,143,142,.18)]');
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-bold shadow-badge gap-[7px] ' . $variantClass]) }}>
    @if($dot)
        <span class="h-[7px] w-[7px] rounded-full {{ $dotColor }}"></span>
    @endif
    {{ $slot }}
</span>
