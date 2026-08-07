@props(['title' => null])

@php
    $hasAction = isset($action) && $action instanceof \Illuminate\View\ComponentSlot && $action->isNotEmpty();
@endphp

<div {{ $attributes->merge(['class' => 'rounded-3xl bg-white/[.66] p-6 shadow-card backdrop-blur-[14px]']) }}>
    @if($title || $hasAction)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            @if($title)
                <h3 class="text-[15px] font-extrabold text-gray-900">{{ $title }}</h3>
            @else
                <span></span>
            @endif
            @if($hasAction)
                <div class="flex flex-wrap items-center gap-2">{{ $action }}</div>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>
