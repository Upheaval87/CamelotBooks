@props(['icon' => null, 'title' => null, 'columns' => null, 'titleBind' => null])

@php
    $hasActions = isset($actions) && $actions instanceof \Illuminate\View\ComponentSlot && $actions->isNotEmpty();
    $gridClass = match ((int) $columns) {
        4 => 'grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2 lg:grid-cols-4',
        3 => 'grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2 lg:grid-cols-3',
        2 => 'grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2',
        default => null,
    };
@endphp

<section {{ $attributes->merge(['class' => 'rounded-3xl bg-white/[.66] p-[26px] shadow-card backdrop-blur-[14px]']) }}>
    @if($titleBind || $title || $icon || $hasActions)
        <div class="flex items-center gap-3">
            @if($icon)
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-[9px] bg-gradient-to-b from-[#17565D] to-[#0C3539] text-[#DFF7F6] shadow-[inset_0_1px_0_rgba(255,255,255,.10),0_3px_8px_-3px_rgba(0,0,0,.4)]">
                    <svg class="h-[15px] w-[15px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                    </svg>
                </span>
            @endif
            @if($titleBind)
                <h2 class="text-[15px] font-extrabold text-gray-900" x-text="{{ $titleBind }}"></h2>
            @else
                <h2 class="text-[15px] font-extrabold text-gray-900">{{ $title }}</h2>
            @endif
            <span class="h-px flex-1 bg-line"></span>
            @if($hasActions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endif
        </div>
    @endif

    @if($gridClass)
        <div class="mt-[22px] {{ $gridClass }}">
            {{ $slot }}
        </div>
    @else
        <div class="mt-[22px]">
            {{ $slot }}
        </div>
    @endif
</section>
