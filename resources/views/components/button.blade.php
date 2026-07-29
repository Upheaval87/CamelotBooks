@props(['variant' => 'ghost', 'href' => null, 'disabled' => false, 'title' => null, 'type' => null])

@php
    $variantClasses = [
        'primary' => 'btn-primary',
        'danger'  => 'btn-danger',
        'commit'  => 'btn-commit',
        'ghost'   => 'btn-ghost',
    ][$variant] ?? 'btn-ghost';

    if ($disabled) {
        $variantClasses .= ' opacity-40 cursor-not-allowed pointer-events-none';
    }
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $variantClasses]) }} @if($title) title="{{ $title }}" @endif>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $variantClasses, 'type' => $type ?? 'button']) }} @if($disabled) disabled @endif @if($title) title="{{ $title }}" @endif>
        {{ $slot }}
    </button>
@endif
