@props(['align' => 'left'])

@php
    $alignClass = match ($align) {
        'right' => 'text-right',
        'center' => 'text-center',
        default => 'text-left',
    };
@endphp

<th {{ $attributes->merge(['class' => 'bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 px-5 py-4 ' . $alignClass . ' text-[11px] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead']) }}>
    {{ $slot }}
</th>
