@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block ps-3 pe-4 py-2 border-l-4 border-atlas-blue text-base font-medium text-atlas-navy bg-atlas-blue/10 focus:outline-none focus:text-atlas-blue focus:bg-atlas-blue/10 focus:border-atlas-blue transition duration-150 ease-in-out'
            : 'block ps-3 pe-4 py-2 border-l-4 border-transparent text-base font-medium text-navy-400 hover:text-atlas-navy hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-atlas-navy focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
