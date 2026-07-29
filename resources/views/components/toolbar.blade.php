@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'toolbar ' . $class]) }}>
    <div class="flex items-center gap-0.5 flex-wrap">
        {{ $slot }}
    </div>
    @isset($right)
        <div class="flex items-center gap-0.5 flex-wrap shrink-0">
            {{ $right }}
        </div>
    @endisset
</div>
