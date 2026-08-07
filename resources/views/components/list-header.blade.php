@props(['title' => '', 'createRoute' => '', 'createLabel' => ''])

<div class="list-header">
    <h1 class="list-header-title">{{ $title }}</h1>
    @if($createRoute)
    <a href="{{ $createRoute }}" class="list-header-create">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        {{ $createLabel ?: __('Create') }}
    </a>
    @endif
</div>
