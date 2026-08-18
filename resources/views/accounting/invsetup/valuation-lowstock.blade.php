<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-crumbs">
            <a href="{{ route('accounting.inventory.dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="sep">/</span>
            <span>{{ __('Inventory Valuation & Low Stock') }}</span>
        </div>
        <div class="inv-head">
            <div>
                <h1>{{ __('Inventory Valuation & Low Stock') }}</h1>
                <div class="inv-sub">{{ __('Current stock levels, valuations, and reorder alerts.') }}</div>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'valuation'])

        <div class="inv-kpi inv-kpi-hero" style="margin-bottom:24px">
            <div class="inv-kpi-label" style="color:#c6e6ec">{{ __('Total Inventory Value') }}</div>
            <div class="inv-kpi-val tabular-nums" style="font-size:36px;color:#FFFFFF">K {{ number_format($valuationTotal ?? 0, 2) }}</div>
            <div class="inv-kpi-sub" style="color:#a2d4d9">{{ __('Across all tracked items') }}</div>
        </div>

        <div class="inv-card">
            <div class="inv-card-h">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                </div>
                {{ __('Stock Valuation by Item') }}
            </div>
            <div class="inv-card-body">
                @forelse($trackedProducts as $item)
                <div style="padding:12px 20px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr;gap:12px;align-items:center;font-size:13px">
                    <div>
                        <span style="font-weight:700;color:var(--ink)">{{ $item->name }}</span>
                        <span style="color:var(--faint);margin-left:6px">{{ $item->sku }}</span>
                    </div>
                    <div class="tabular-nums">{{ $item->stock_qty ?? 0 }}</div>
                    <div class="tabular-nums">{{ $item->sales_price ? 'K ' . number_format($item->sales_price, 2) : '—' }}</div>
                    <div class="tabular-nums" style="font-weight:700;color:var(--ink)">K {{ number_format(($item->stock_qty ?? 0) * ($item->sales_price ?? 0), 2) }}</div>
                    <div style="display:flex;justify-content:flex-end">
                        @if(($item->stock_qty ?? 0) <= ($item->reorder_point ?? 0))
                            <span class="inv-pill-negative">{{ __('Low') }}</span>
                        @else
                            <span class="inv-pill-positive">{{ __('OK') }}</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="inv-empty">
                    <div class="inv-empty-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                    </div>
                    <p>{{ __('No items tracked.') }}</p>
                    <div class="inv-empty-sub">{{ __('Add inventory items and enable tracking to see valuation data.') }}</div>
                </div>
                @endforelse
            </div>
        </div>

        <div class="inv-card" style="margin-top:24px">
            <div class="inv-card-h">
                <div class="inv-sec-ic inv-sec-ic-amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                {{ __('Low Stock Items') }}
            </div>
            <div class="inv-card-body">
                @forelse($lowStockItems as $item)
                <div style="padding:12px 20px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:12px;align-items:center;font-size:13px">
                    <div>
                        <span style="font-weight:700;color:var(--ink)">{{ $item->name }}</span>
                        <span style="color:var(--faint);margin-left:6px">{{ $item->sku }}</span>
                    </div>
                    <div class="tabular-nums" style="color:var(--red)">{{ $item->stock_on_hand }}</div>
                    <div class="tabular-nums">{{ $item->reorder_point ?? '—' }}</div>
                    <div class="tabular-nums" style="color:var(--red);font-weight:700">{{ $item->reorder_point ? ($item->stock_on_hand - $item->reorder_point) : '—' }}</div>
                </div>
                @empty
                <div class="inv-empty">
                    <div class="inv-empty-ic inv-empty-ic-ok">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <p>{{ __('All good — no items below reorder point.') }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
