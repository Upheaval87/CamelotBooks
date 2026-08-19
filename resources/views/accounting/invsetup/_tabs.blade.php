@php
    $activeTab = $activeTab ?? '';
    $lowStockCount = \App\Models\Product::forCompany(session('current_company_id'))
        ->where('tracked_as_inventory', true)
        ->where('reorder_point', '>', 0)
        ->get()
        ->filter(fn ($p) => $p->stock_qty <= $p->reorder_point)
        ->count();
@endphp
<nav class="segbar" aria-label="{{ __('Inventory sections') }}">
    <a href="{{ route('accounting.invsetup.categories') }}" class="{{ $activeTab === 'categories' ? 'on' : '' }}" {{ $activeTab === 'categories' ? 'aria-current="page"' : '' }}>
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
        {{ __('Item Categories') }}
    </a>
    <a href="{{ route('accounting.invsetup.assemblies') }}" class="{{ $activeTab === 'assemblies' ? 'on' : '' }}" {{ $activeTab === 'assemblies' ? 'aria-current="page"' : '' }}>
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
        {{ __('Assemblies') }}
    </a>
    <a href="{{ route('accounting.invsetup.transfers') }}" class="{{ $activeTab === 'transfers' ? 'on' : '' }}" {{ $activeTab === 'transfers' ? 'aria-current="page"' : '' }}>
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
        {{ __('Transfers & Adjustments') }}
    </a>
    <a href="{{ route('accounting.invsetup.stockcount') }}" class="{{ $activeTab === 'counts' ? 'on' : '' }}" {{ $activeTab === 'counts' ? 'aria-current="page"' : '' }}>
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="15" y2="16"/></svg>
        {{ __('Stock Count') }}
    </a>
    <a href="{{ route('accounting.invsetup.uom') }}" class="{{ $activeTab === 'uom' ? 'on' : '' }}" {{ $activeTab === 'uom' ? 'aria-current="page"' : '' }}>
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
        {{ __('UOM & Landed Costs') }}
    </a>
    <a href="{{ route('accounting.invsetup.valuation') }}" class="{{ $activeTab === 'valuation' ? 'on' : '' }}" {{ $activeTab === 'valuation' ? 'aria-current="page"' : '' }}>
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        {{ __('Valuation') }}
    </a>
    <a href="{{ route('accounting.invsetup.lowstock') }}" class="{{ $activeTab === 'lowstock' ? 'on' : '' }}" {{ $activeTab === 'lowstock' ? 'aria-current="page"' : '' }}>
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        {{ __('Low Stock') }}
        @if($lowStockCount > 0)
        <span class="b" aria-label="{{ __(':count items low or out', ['count' => $lowStockCount]) }}">{{ $lowStockCount }}</span>
        @endif
    </a>
</nav>
