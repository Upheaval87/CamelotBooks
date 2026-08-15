@props(['title', 'description' => null, 'chip', 'tone' => 'approved'])

@php
    $iconPath = $tone === 'rejected'
        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'
        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-4 rounded-3xl bg-gradient-to-r from-navy-700 via-navy-800 to-navy-900 p-6 shadow-card']) }}>
    <span class="flex h-11 w-11 items-center justify-center rounded-[14px] bg-gold-500/[.16] text-[#DFF7F6] shadow-edit">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $iconPath !!}</svg>
    </span>
    <div class="min-w-0 flex-1">
        <p class="text-[1.071rem] font-bold text-white">{{ $title }}</p>
        @if($description)
            <p class="mt-0.5 text-[0.964rem] text-navy-200">{{ $description }}</p>
        @endif
    </div>
    <span class="rounded-full border border-[#DFF7F6]/30 bg-[#DFF7F6]/10 px-3 py-1.5 font-mono text-xs font-bold tracking-[0.08em] text-[#DFF7F6]">{{ $chip }}</span>
</div>
