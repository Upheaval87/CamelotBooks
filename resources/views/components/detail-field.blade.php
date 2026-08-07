@props(['label' => '', 'value' => '', 'strong' => false, 'class' => ''])
<div class="detail-field {{ $class }}">
    <p class="detail-lbl">{{ $label }}</p>
    <div class="detail-val {{ $strong ? 'strong' : '' }}{{ $attributes->has('no-border') || $attributes->has('noBorder') ? ' no-border' : '' }}">
        {{ $slot->isNotEmpty() ? $slot : $value }}
    </div>
</div>