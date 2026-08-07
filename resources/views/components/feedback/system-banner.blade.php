@props([
    'title' => null,
    'actionLabel' => null,
    'actionUrl' => '#',
])

<div
    x-data="{ visible: true }"
    x-show="visible"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="fb-banner"
    role="status"
>
    <span class="fb-banner__icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
    </span>
    <span class="fb-banner__content">
        @if($title)
            <span class="fb-banner__title">{{ $title }}</span>
        @endif
        <span class="fb-banner__body">{{ $slot }}</span>
    </span>
    @if($actionLabel)
        <a href="{{ $actionUrl }}" class="fb-banner__action">{{ $actionLabel }}</a>
    @endif
    <button type="button" class="fb-banner__dismiss" aria-label="Dismiss" @click="visible = false">&times;</button>
</div>
