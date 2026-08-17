@php
    $activeTab = $activeTab ?? 'dashboard';
    $tabs = [
        ['key' => 'dashboard', 'label' => __('Dashboard'), 'route' => 'accounting.budgets.dashboard'],
        ['key' => 'index',     'label' => __('All Budgets'), 'route' => 'accounting.budgets.index'],
        ['key' => 'create',    'label' => __('Create Budget'), 'route' => 'accounting.budgets.create'],
        ['key' => 'settings',  'label' => __('Settings'), 'route' => 'accounting.budgets.settings'],
        ['key' => 'reports',   'label' => __('Reports'), 'route' => 'accounting.budgets.reports'],
    ];
@endphp
<nav class="bu-subnav" aria-label="{{ __('Budgeting') }}">
    <span class="bu-subnav-grp">
        <span class="bu-subnav-ic">
            <svg viewBox="0 0 24 24"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
        </span>
        {{ __('Budgeting') }}
    </span>
    <span class="bu-vline"></span>
    @foreach($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="bu-stab @if($activeTab === $tab['key']) on @endif"
           @if($activeTab === $tab['key']) aria-current="page" @endif>
            {{ $tab['label'] }}
            @if($tab['new'] ?? false)
                <span class="bu-new">NEW</span>
            @endif
        </a>
    @endforeach
    <span class="bu-subnav-spacer"></span>
</nav>
