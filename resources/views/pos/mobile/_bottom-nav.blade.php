@php
    $active = $active ?? 'home';
    $navItems = [
        ['key' => 'home', 'label' => 'Home', 'route' => 'pos.m.home', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
        ['key' => 'receipts', 'label' => 'Receipts', 'route' => 'pos.m.receipts', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
        ['key' => 'products', 'label' => 'Products', 'route' => 'pos.m.products', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>'],
        ['key' => 'more', 'label' => 'More', 'route' => 'pos.m.settings', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>'],
    ];
@endphp
<nav class="pos-m-nav">
    @foreach(array_slice($navItems, 0, 2) as $item)
        <a href="{{ route($item['route']) }}" class="pos-m-nav-b {{ $active === $item['key'] ? 'on' : '' }}">
            <svg class="pos-m-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $item['icon'] !!}</svg>
            <span>{{ $item['label'] }}</span>
            <span class="pos-m-nav-dot"></span>
        </a>
    @endforeach
    <a href="{{ route('pos.m.sell') }}" class="pos-m-nav-fab">+</a>
    @foreach(array_slice($navItems, 2) as $item)
        <a href="{{ route($item['route']) }}" class="pos-m-nav-b {{ $active === $item['key'] ? 'on' : '' }}">
            <svg class="pos-m-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $item['icon'] !!}</svg>
            <span>{{ $item['label'] }}</span>
            <span class="pos-m-nav-dot"></span>
        </a>
    @endforeach
</nav>
