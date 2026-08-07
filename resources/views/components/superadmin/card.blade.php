@props(['title' => null])

<x-ui.card :title="$title" {{ $attributes }}>
    @if(isset($action) && $action->isNotEmpty())
        <x-slot name="action">{{ $action }}</x-slot>
    @endif
    {{ $slot }}
</x-ui.card>
