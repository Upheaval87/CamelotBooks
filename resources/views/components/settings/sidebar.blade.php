@props(['activeTab' => 'company', 'tabMap' => [], 'groups' => []])

@php
$tabs = [
    'company'   => ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-4 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'Company Profile'],
    'regional'  => ['icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Regional Settings'],
    'currency'  => ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Currency Settings'],
    'accounts'  => ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Account Mappings'],
    'accounting'=> ['icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'label' => 'Accounting Settings'],
    'approval'  => ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Approval Settings'],
    'notifications' => ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'label' => 'Email'],
    'data-hub'  => ['icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4', 'label' => 'Data Hub'],
    'import-export' => ['icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12', 'label' => 'Import/Export'],
    'backups'   => ['icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'label' => 'Backups'],
    'audit-log' => ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'label' => 'Audit Log'],
];

$grouped = [];
$groupIndex = 0;
foreach ($groups as $group) {
    $items = [];
    foreach ($group as $key) {
        if (isset($tabs[$key])) {
            $items[$key] = $tabs[$key];
        }
    }
    if (!empty($items)) {
        $grouped[$groupIndex++] = $items;
    }
}
@endphp

<nav class="settings-sidebar" x-data="{ mobileOpen: false }">
    {{-- Mobile trigger --}}
    <button @click="mobileOpen = !mobileOpen" class="settings-sidebar-mobile-trigger lg:hidden">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span>{{ $tabs[$activeTab]['label'] ?? 'Settings' }}</span>
        <svg class="w-4 h-4 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    {{-- Desktop sidebar --}}
    <div class="hidden lg:flex lg:flex-col settings-sidebar-desktop">
        @foreach($grouped as $gi => $items)
            @if($gi > 0)
                <div class="settings-sidebar-divider"></div>
            @endif
            @foreach($items as $key => $tab)
                @php
                    $isActive = $key === $activeTab;
                    $route = $key === 'backups' ? route('admin.backups.index') : ($key === 'audit-log' ? route('system-settings.audit-log') : route('system-settings.index', $key));
                @endphp
                <a href="{{ $route }}"
                   class="settings-sidebar-item {{ $isActive ? 'settings-sidebar-item-active' : '' }}">
                    <svg class="settings-sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}" />
                    </svg>
                    <span>{{ $tab['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </div>

    {{-- Mobile dropdown --}}
    <div x-show="mobileOpen" x-cloak @click.away="mobileOpen = false" class="lg:hidden settings-sidebar-mobile-dropdown">
        @foreach($grouped as $gi => $items)
            @if($gi > 0)
                <div class="settings-sidebar-divider"></div>
            @endif
            @foreach($items as $key => $tab)
                @php
                    $isActive = $key === $activeTab;
                    $route = $key === 'backups' ? route('admin.backups.index') : ($key === 'audit-log' ? route('system-settings.audit-log') : route('system-settings.index', $key));
                @endphp
                <a href="{{ $route }}"
                   class="settings-sidebar-item {{ $isActive ? 'settings-sidebar-item-active' : '' }}">
                    <svg class="settings-sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}" />
                    </svg>
                    <span>{{ $tab['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </div>
</nav>
