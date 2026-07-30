@props(['name' => '', 'label' => '', 'description' => '', 'checked' => false, 'value' => '1'])

<div class="settings-toggle-card">
    <label class="settings-toggle-row">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}"
            {{ $checked ? 'checked' : '' }}
            {{ $attributes->merge(['class' => 'settings-toggle-input']) }}>
        <span class="settings-toggle-track">
            <span class="settings-toggle-thumb"></span>
        </span>
        <div class="settings-toggle-text">
            <span class="settings-toggle-label">{{ $label }}</span>
            @if($description)
            <span class="settings-toggle-desc">{{ $description }}</span>
            @endif
        </div>
    </label>
</div>
