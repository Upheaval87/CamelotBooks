@props(['variant' => 'info'])

@php
$classes = $variant === 'warn'
    ? 'settings-callout settings-callout-warn'
    : 'settings-callout settings-callout-info';
$iconPath = $variant === 'warn'
    ? 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z'
    : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
$lead = $variant === 'warn' ? 'Warning:' : 'Note:';
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    <svg class="settings-callout-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
    </svg>
    <div>
        <strong>{{ __($lead) }}</strong>
        {{ $slot }}
    </div>
</div>
