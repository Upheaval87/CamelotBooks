@props(['label' => '', 'name' => '', 'required' => false, 'hint' => '', 'error' => '', 'type' => 'text', 'value' => '', 'placeholder' => '', 'options' => [], 'model' => null])

<div class="field">
    @if($label)
    <label for="{{ $name }}" class="label">
        {{ $label }}
        @if($required)<span class="req">*</span>@endif
    </label>
    @endif

    @if($type === 'select')
    <select name="{{ $name }}" id="{{ $name }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'input']) }}>
        {{ $slot }}
    </select>
    @elseif($type === 'textarea')
    <textarea name="{{ $name }}" id="{{ $name }}" rows="3"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'input']) }}>{{ $value }}</textarea>
    @elseif($type === 'file')
    <input type="file" name="{{ $name }}" id="{{ $name }}"
        {{ $attributes->merge(['class' => 'input']) }}>
    @else
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'input']) }}>
    @endif

    @if($hint)
    <p class="hint">{{ $hint }}</p>
    @endif
    @if($error)
    <p class="err">{{ $error }}</p>
    @endif
</div>
