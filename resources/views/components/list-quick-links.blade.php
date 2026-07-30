@props(['title' => 'Quick Links', 'groups' => []])

<aside class="list-sidebar" x-data="{ mobileOpen: false }">
    <button @click="mobileOpen = !mobileOpen" class="list-sidebar-mobile-trigger lg:hidden">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        <span>Quick Links</span>
        <svg class="w-4 h-4 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div class="list-sidebar-desktop">
        <div class="list-sidebar-label">{{ $title }}</div>

        @foreach($groups as $groupIndex => $group)
            @if($groupIndex > 0)
                <div class="list-sidebar-divider"></div>
            @endif
            @foreach($group as $link)
                <a href="{{ $link['route'] }}" class="list-sidebar-link">
                    <span class="list-sidebar-icon-square">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" />
                        </svg>
                    </span>
                    <span class="list-sidebar-link-text">
                        <span class="list-sidebar-link-title">{{ $link['title'] }}</span>
                        @if(!empty($link['subtitle']))
                        <span class="list-sidebar-link-subtitle">{{ $link['subtitle'] }}</span>
                        @endif
                    </span>
                </a>
            @endforeach
        @endforeach
    </div>

    <div x-show="mobileOpen" x-cloak @click.away="mobileOpen = false" class="list-sidebar-mobile-dropdown lg:hidden">
        <div class="list-sidebar-label">{{ $title }}</div>
        @foreach($groups as $groupIndex => $group)
            @if($groupIndex > 0)
                <div class="list-sidebar-divider"></div>
            @endif
            @foreach($group as $link)
                <a href="{{ $link['route'] }}" class="list-sidebar-link">
                    <span class="list-sidebar-icon-square">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" />
                        </svg>
                    </span>
                    <span class="list-sidebar-link-text">
                        <span class="list-sidebar-link-title">{{ $link['title'] }}</span>
                        @if(!empty($link['subtitle']))
                        <span class="list-sidebar-link-subtitle">{{ $link['subtitle'] }}</span>
                        @endif
                    </span>
                </a>
            @endforeach
        @endforeach
    </div>
</aside>
