@props(['title' => null, 'icon' => null, 'hint' => null])

@php
    $hasActions = isset($actions) && $actions instanceof \Illuminate\View\ComponentSlot && $actions->isNotEmpty();
    $hasFields = isset($fields) && $fields instanceof \Illuminate\View\ComponentSlot && $fields->isNotEmpty();
@endphp

<div {{ $attributes->merge(['class' => 'rounded-3xl bg-white/[.66] p-[26px] shadow-card backdrop-blur-[14px]']) }}>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-[12px] bg-gradient-to-b from-[#17565D] to-[#0C3539] text-[#DFF7F6] shadow-edit">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $icon ?? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>' !!}</svg>
            </span>
            <h3 class="text-[15px] font-extrabold text-gray-900">{{ $title ?? __('Review & Decide') }}</h3>
        </div>
    </div>

    @if($hasFields)
        <div class="mt-[22px] grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-2">{{ $fields }}</div>
    @endif

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3.5 border-t border-line pt-[18px]">
        @if($hint)
            <p class="text-sm text-gray-500">{{ $hint }}</p>
        @else
            <span></span>
        @endif
        @if($hasActions)
            <div class="flex flex-wrap items-center gap-2.5">{{ $actions }}</div>
        @endif
    </div>
</div>
