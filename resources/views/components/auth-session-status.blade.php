@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'badge-success text-sm px-4 py-2.5 rounded-lg w-full']) }}>
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ $status }}</span>
    </div>
@endif
