@props(['variant' => 'muted'])

@php
    $variants = [
        'active' => 'border-green-600/30 bg-gradient-to-b from-mint-100 to-mint-200 text-green-700',
        'muted' => 'border-gray-200 bg-gradient-to-b from-gray-50 to-gray-100 text-gray-500',
        'warning' => 'border-amber-300/60 bg-gradient-to-b from-amber-50 to-amber-100 text-amber-700',
        'danger' => 'border-red-300/60 bg-gradient-to-b from-red-50 to-red-100 text-red-700',
        'accent' => 'border-gold-600/30 bg-gradient-to-b from-[#F4FBFB] to-[#DFF7F6] text-gold-700',
        'core' => 'border-gold-600/30 bg-gradient-to-b from-[#F4FBFB] to-[#DFF7F6] text-gold-700',
        'navy' => 'border-navy-700/[.22] bg-navy-700/[.08] text-navy-700',
        'positive' => 'border-green-600/30 bg-gradient-to-b from-mint-100 to-mint-200 text-green-700',
        'neutral' => 'border-gray-200 bg-gradient-to-b from-gray-50 to-gray-100 text-gray-500',
    ];
    $base = 'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-bold shadow-badge ';
@endphp

@if($variant === 'active' || $variant === 'positive')
    <span {{ $attributes->merge(['class' => $base . 'gap-[7px] ' . $variants[$variant]]) }}>
        <span class="h-[7px] w-[7px] rounded-full bg-green-500 shadow-[0_0_0_3px_rgba(34,197,94,.18)]"></span>
        {{ $slot }}
    </span>
@else
    <span {{ $attributes->merge(['class' => $base . ($variants[$variant] ?? $variants['muted'])]) }}>{{ $slot }}</span>
@endif
