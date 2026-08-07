@props(['title' => '', 'createRoute' => '', 'createLabel' => '', 'description' => null])

<x-ui.page-head :title="$title" :description="$description" {{ $attributes }}>
    @if($slot->isNotEmpty())
        {{ $slot }}
    @elseif($createRoute)
        <x-ui.btn href="{{ $createRoute }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ $createLabel ?: __('Create') }}
        </x-ui.btn>
    @endif
</x-ui.page-head>
