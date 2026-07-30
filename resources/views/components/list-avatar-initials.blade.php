@props(['name' => '', 'size' => 'md'])

@php
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $w) {
        if (mb_strlen($w) > 0) {
            $initials .= mb_strtoupper(mb_substr($w, 0, 1));
        }
    }
    $initials = mb_substr($initials, 0, 2);
    $colors = ['#E8E4D9', '#D4C9B0', '#C4B89C', '#B8A88C', '#A89878', '#E0DCD0'];
    $colorIdx = abs(crc32($name ?: '?')) % count($colors);
@endphp

<span class="avatar-initials avatar-initials-{{ $size }}" style="background-color: {{ $colors[$colorIdx] }}">{{ $initials }}</span>
