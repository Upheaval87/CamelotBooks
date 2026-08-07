@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'mb-[22px] flex flex-wrap items-start justify-between gap-5']) }}>
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">{{ $title }}</h1>
            @if(isset($badge) && $badge->isNotEmpty())
                {{ $badge }}
            @endif
        </div>
        @if($description)
            <p class="mt-1.5 text-sm text-gray-500">{{ $description }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2.5">{{ $slot }}</div>
    @endif
</div>
