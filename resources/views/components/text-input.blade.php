@props(['value', 'type' => 'text', 'disabled' => false])

<input
    type="{{ $type }}"
    {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge([
        'class' => 'block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-atlas-blue focus:ring-1 focus:ring-atlas-blue focus:shadow-[0_0_0_3px_rgba(101,145,224,0.2)] transition-all'
    ]) !!}
>
