@props(['title', 'description' => null])

<div class="mb-[22px] flex flex-wrap items-start justify-between gap-5">
    <div class="min-w-0">
        <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">{{ $title }}</h1>
        @if($description)
            <p class="mt-2 max-w-[720px] text-sm leading-relaxed text-gray-500">{{ $description }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2.5">{{ $slot }}</div>
    @endif
</div>
