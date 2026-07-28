@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-atlas-navy']) }}>
    {{ $value ?? $slot }}
</label>
