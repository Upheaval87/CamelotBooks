@props(['title' => null, 'icon' => null])

@php
    $hasAction = isset($action) && $action instanceof \Illuminate\View\ComponentSlot && $action->isNotEmpty();
@endphp

<div {{ $attributes->merge(['class' => 'rounded-3xl bg-white/[.66] p-[26px] shadow-card backdrop-blur-[14px]']) }}>
    @if($title || $icon || $hasAction)
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                @if($icon)
                    <span class="flex h-10 w-10 items-center justify-center rounded-[12px] bg-gradient-to-b from-[#17565D] to-[#0C3539] text-[#DFF7F6] shadow-edit">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $icon !!}</svg>
                    </span>
                @endif
                @if($title)
                    <h3 class="text-[1.071rem] font-extrabold text-gray-900">{{ $title }}</h3>
                @endif
            </div>
            @if($hasAction)
                <div class="flex flex-wrap items-center gap-2">{{ $action }}</div>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>
