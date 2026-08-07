@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button'])

@php
    $variants = [
        'primary' => 'bg-gradient-to-b from-gold-500 to-gold-600 text-white shadow-new hover:-translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500',
        'reject' => 'border border-red-600/35 bg-white text-red-600 hover:border-red-600/55 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500',
        'ghost' => 'border border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500',
        'edit' => 'border border-gold-600/35 bg-gradient-to-b from-[#fffdf8] to-[#f7f0df] text-gold-700 shadow-edit hover:-translate-y-px hover:border-gold-600/55 hover:text-gold-800 hover:shadow-edit-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500',
    ];
    $sizes = [
        'lg' => 'rounded-[12px] gap-2 px-5 py-3 text-sm font-semibold',
        'md' => 'rounded-[10px] gap-1.5 px-4 py-2 text-[13px] font-bold',
    ];
    $classes = 'inline-flex items-center justify-center transition ' . $sizes[$size] . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
