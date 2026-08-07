@props(['label', 'value' => null, 'class' => '', 'mono' => false])

<div class="{{ $class }}">
    <p class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-slate-500">{{ $label }}</p>
    <div class="{{ $mono ? 'font-mono text-[13px] font-medium' : 'text-sm font-semibold' }} leading-relaxed text-gray-900">
        {{ $slot ?? $value ?? '—' }}
    </div>
</div>
