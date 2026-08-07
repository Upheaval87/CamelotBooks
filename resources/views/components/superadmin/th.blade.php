@props(['align' => 'left'])

<x-ui.th :align="$align" {{ $attributes }}>
    {{ $slot }}
</x-ui.th>
