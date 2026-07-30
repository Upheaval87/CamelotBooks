@props(['title' => 'Quick Actions', 'groups' => []])

<aside class="form-page-sidebar">
    <div class="form-sidebar-card">
        @if($title)
            <div class="form-sidebar-group-label">{{ $title }}</div>
        @endif
        @foreach($groups as $groupIndex => $group)
            @if($groupIndex > 0)
                <div class="form-sidebar-divider"></div>
            @endif
            @if(!empty($group['label']))
                <div class="form-sidebar-group-label">{{ $group['label'] }}</div>
            @endif
            @foreach($group['links'] ?? [] as $link)
                <a href="{{ $link['route'] }}" class="form-sidebar-link">
                    @if(!empty($link['icon']))
                        {!! $link['icon'] !!}
                    @endif
                    <span>{{ $link['title'] }}</span>
                </a>
            @endforeach
        @endforeach
        @if(!$groups)
            {{ $slot ?? '' }}
        @endif
    </div>
</aside>
