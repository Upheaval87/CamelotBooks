@props(['icon' => 'star', 'class' => 'w-4 h-4'])

<span class="inline-flex {!! $class !!}">
    {!! \App\Services\FavouritesService::svg($icon) !!}
</span>
