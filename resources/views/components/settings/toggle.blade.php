@props(['name' => '', 'label' => '', 'description' => '', 'checked' => false, 'value' => '1'])

<div class="toggle-row">
    <label class="toggle-ui">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}"
            {{ $checked ? 'checked' : '' }}
            {{ $attributes->merge(['class' => 'toggle-input']) }}>
        <span class="toggle-track">
            <span class="toggle-thumb"></span>
        </span>
    </label>
    <div class="toggle-text">
        <span class="toggle-label">{{ $label }}</span>
        @if($description)
        <span class="toggle-desc">{{ $description }}</span>
        @endif
    </div>
</div>
