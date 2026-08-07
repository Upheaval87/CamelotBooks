@props([
    'variant' => 'info', // info | success | warning | error
    'title' => null,
])

@php
$icons = [
    'info' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    'success' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
    'warning' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.3 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.7 3.86a2 2 0 00-3.4 0z"/></svg>',
    'error' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v2m0 4h.01"/></svg>',
];
$role = in_array($variant, ['warning', 'error'], true) ? 'alert' : 'status';
@endphp

<div {{ $attributes->class(['fb-alert', 'fb-alert--' . $variant]) }} role="{{ $role }}">
    <span class="fb-alert__icon">{!! $icons[$variant] ?? $icons['info'] !!}</span>
    <span class="fb-alert__msg">
        @if($title)
            <span class="fb-alert__title">{{ $title }}</span>
        @endif
        {{ $slot }}
    </span>
</div>
