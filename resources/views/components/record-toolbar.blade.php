@props(['class' => ''])
<div {{ $attributes->merge(['class' => 'record-toolbar ' . $class]) }}>
    <div class="tr-row">
        {{ $slot }}
    </div>
</div>