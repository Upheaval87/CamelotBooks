<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-head">
            <div>
                <h1>{{ $product->name }}</h1>
                <div class="inv-sub">
                    <span class="inv-chip">{{ $product->sku }}</span>
                    <span class="inv-badge inv-badge-{{ $product->is_active ? 'active' : 'inactive' }}">{{ $product->is_active ? __('Active') : __('Inactive') }}</span>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <a href="{{ route('accounting.inventory.items.edit', $product) }}" class="inv-btn inv-btn-ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('accounting.inventory.items') }}" class="inv-btn inv-btn-ghost">{{ __('Back to Items') }}</a>
            </div>
        </div>

        <div class="inv-shell">
            <div>
                <div class="inv-card">
                    <div class="inv-card-head"><h2>{{ __('Item Information') }}</h2></div>
                    <div class="inv-detail">
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Type') }}</div><div class="inv-detail-v">{{ ucfirst($product->type) }}</div></div>
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Category') }}</div><div class="inv-detail-v">{{ $product->itemCategory?->name ?? '—' }}</div></div>
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Unit of Measure') }}</div><div class="inv-detail-v">{{ $product->unit_of_measure ?? '—' }}</div></div>
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Barcode') }}</div><div class="inv-detail-v inv-mono">{{ $product->barcode ?? '—' }}</div></div>
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Tax Rate') }}</div><div class="inv-detail-v">{{ $product->tax_rate }}%</div></div>
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Track Inventory') }}</div><div class="inv-detail-v">{{ $product->tracked_as_inventory ? __('Yes') : __('No') }}</div></div>
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Description') }}</div><div class="inv-detail-v">{{ $product->description ?? '—' }}</div></div>
                    </div>
                </div>

                <div class="inv-card">
                    <div class="inv-card-head"><h2>{{ __('Pricing & GL') }}</h2></div>
                    <div class="inv-detail">
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Sales Price') }}</div><div class="inv-detail-v inv-num">{{ number_format($product->sales_price ?? 0, 2) }}</div></div>
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Purchase Price') }}</div><div class="inv-detail-v inv-num">{{ number_format($product->purchase_price ?? 0, 2) }}</div></div>
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Reorder Point') }}</div><div class="inv-detail-v inv-num">{{ number_format($product->reorder_point ?? 0, 0) }}</div></div>
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Income Account') }}</div><div class="inv-detail-v">{{ $product->incomeAccount?->code ? $product->incomeAccount->code.' — '.$product->incomeAccount->name : '—' }}</div></div>
                        <div class="inv-detail-row"><div class="inv-detail-l">{{ __('Expense Account') }}</div><div class="inv-detail-v">{{ $product->expenseAccount?->code ? $product->expenseAccount->code.' — '.$product->expenseAccount->name : '—' }}</div></div>
                    </div>
                </div>
            </div>

            <div class="inv-rail">
                <div class="inv-rail-card">
                    <h3>{{ __('Stock Summary') }}</h3>
                    <div class="inv-kpis" style="grid-template-columns:1fr">
                        <div class="inv-kpi"><div class="inv-kpi-l">{{ __('On Hand') }}</div><div class="inv-kpi-v">{{ number_format($product->stock_qty ?? 0) }}</div></div>
                        <div class="inv-kpi"><div class="inv-kpi-l">{{ __('Reorder Point') }}</div><div class="inv-kpi-v">{{ number_format($product->reorder_point ?? 0) }}</div></div>
                    </div>
                    @if(($product->stock_qty ?? 0) < ($product->reorder_point ?? 0) && $product->reorder_point > 0)
                    <div class="inv-note" style="margin-top:12px;color:var(--red-2,#b91c1c)">{{ __('Stock is below reorder point.') }}</div>
                    @endif
                </div>
                <div class="inv-rail-card">
                    <h3>{{ __('Quick Actions') }}</h3>
                    <a href="{{ route('accounting.inventory.items.edit', $product) }}" class="inv-rail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        {{ __('Edit Item') }}
                    </a>
                    <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-rail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/></svg>
                        {{ __('Transfers & Adjustments') }}
                    </a>
                    <a href="{{ route('accounting.inventory.items') }}" class="inv-rail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        {{ __('All Items') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
