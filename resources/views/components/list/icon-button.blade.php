@props(['tooltip' => ''])

<button {{ $attributes->merge(['type' => 'button', 'class' => 'icon-btn']) }}>
    {{ $slot }}
    @if($tooltip)
    <span class="icon-btn-tooltip">{{ $tooltip }}</span>
    @endif
</button>
