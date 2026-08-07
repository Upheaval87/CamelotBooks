@props(['title', 'subtitle' => null, 'backUrl' => null, 'backLabel' => null])

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">{{ $title }}</h1>
            @if(isset($badge) && $badge->isNotEmpty())
                {{ $badge }}
            @endif
        </div>
        @if($subtitle)
            <p class="mt-1.5 text-[13.5px] text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if($backUrl)
        <a href="{{ $backUrl }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-shell bg-white/80 px-4 text-sm font-semibold text-gray-700 shadow-edit transition hover:-translate-y-px hover:border-gray-400 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ $backLabel ?? __('Back') }}
        </a>
    @endif
</div>
