@props(['label' => '', 'name' => '', 'required' => false, 'hint' => '', 'error' => '', 'type' => 'text', 'value' => '', 'placeholder' => '', 'options' => [], 'model' => null])

<div class="settings-field">
    @if($label)
    <label for="{{ $name }}" class="settings-field-label">
        {{ $label }}
        @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
    </label>
    @endif

    @if($type === 'select')
    <select name="{{ $name }}" id="{{ $name }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'settings-field-input']) }}>
        {{ $slot }}
    </select>
    @elseif($type === 'textarea')
    <textarea name="{{ $name }}" id="{{ $name }}" rows="3"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'settings-field-input']) }}>{{ $value }}</textarea>
    @elseif($type === 'file')
    <input type="file" name="{{ $name }}" id="{{ $name }}"
        {{ $attributes->merge(['class' => 'settings-field-file']) }}>
    @else
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'settings-field-input']) }}>
    @endif

    @if($hint)
    <p class="settings-field-hint">{{ $hint }}</p>
    @endif
    @if($error)
    <p class="settings-field-error">{{ $error }}</p>
    @endif
</div>
