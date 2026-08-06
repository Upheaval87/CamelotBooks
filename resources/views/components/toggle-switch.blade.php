@props(['checked' => false, 'ariaLabel' => null, 'title' => null])

<button type="submit" role="switch" aria-checked="{{ $checked ? 'true' : 'false' }}"
        {{ $attributes->merge(['class' => 'sa-toggle' . ($checked ? ' is-on' : '')]) }}
        @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
        @if($title) title="{{ $title }}" @endif>
    <span class="sa-toggle-knob"></span>
</button>
