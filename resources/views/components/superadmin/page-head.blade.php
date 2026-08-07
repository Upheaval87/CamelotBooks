@props(['title', 'description' => null])

<x-ui.page-head :title="$title" :description="$description" {{ $attributes }}>
    @if(isset($badge) && $badge->isNotEmpty())
        <x-slot name="badge">{{ $badge }}</x-slot>
    @endif
    {{ $slot }}
</x-ui.page-head>
