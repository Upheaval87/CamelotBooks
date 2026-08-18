<x-app-layout>
    <x-slot name="header">{{ __('Valuation & Low Stock') }}</x-slot>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <div class="inv-hdr">
            <div>
                <h1 class="inv-hdr-t">{{ __('Valuation & Low Stock') }}</h1>
                <p class="inv-hdr-sub">{{ __('Inventory value by warehouse and items needing attention.') }}</p>
            </div>
            <div class="inv-hdr-acts">
                <button class="inv-btn inv-btn-ghost" type="button" onclick="window.print()">
                    <svg class="inv-btn-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    {{ __('Export CSV') }}
                </button>
            </div>
        </div>

        <div class="inv-tabs">
            <a href="{{ route('accounting.invsetup.categories') }}" class="inv-tab">{{ __('Item Categories') }}</a>
            <a href="{{ route('accounting.invsetup.assemblies') }}" class="inv-tab">{{ __('Assemblies') }}</a>
            <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-tab">{{ __('Transfers & Adjustments') }}</a>
            <a href="{{ route('accounting.invsetup.stockcount') }}" class="inv-tab">{{ __('Stock Count') }}</a>
            <a href="{{ route('accounting.invsetup.uom') }}" class="inv-tab">{{ __('UOM & Landed Costs') }}</a>
            <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-tab inv-tab-on">{{ __('Valuation') }}</a>
            <a href="{{ route('accounting.invsetup.lowstock') }}" class="inv-tab">{{ __('Low Stock') }}</a>
        </div>

        <div class="inv-shell">
            <div class="inv-main">
                {{-- Valuation KPIs --}}
                <div class="inv-sgrid inv-sgrid-3">
                    <div class="inv-sbox">
                        <div class="inv-sbox-ic inv-sbox-ic-ink">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                        </div>
                        <div class="inv-sbox-lbl">{{ __('Total Value') }}</div>
                        <div class="inv-sbox-v">{{ number_format($valuationTotal ?? 0, 2) }}</div>
                    </div>
                    <div class="inv-sbox">
                        <div class="inv-sbox-ic inv-sbox-ic-green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                        </div>
                        <div class="inv-sbox-lbl">{{ __('Items Valued') }}</div>
                        <div class="inv-sbox-v">{{ $itemsValued ?? 0 }}</div>
                    </div>
                    <div class="inv-sbox">
                        <div class="inv-sbox-ic inv-sbox-ic-steel">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </div>
                        <div class="inv-sbox-lbl">{{ __('Active Warehouses') }}</div>
                        <div class="inv-sbox-v">{{ $warehouseCount ?? 0 }}</div>
                    </div>
                </div>

                <div class="inv-callout inv-callout-info">
                    <svg class="inv-callout-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <div>
                        <div class="inv-callout-t">{{ __('Valuation Method') }}: {{ $method ?? 'Weighted Average' }}</div>
                        <p class="inv-callout-desc">{{ __('Inventory value is computed on demand from the movement ledger. Cost layers are never stored; values reflect the most recent FIFO valuation.') }}</p>
                    </div>
                </div>

                <div class="inv-card">
                    <div class="inv-card-h">
                        <svg class="inv-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        <span>{{ __('Inventory by Warehouse') }}</span>
                    </div>
                    <div class="inv-card-body inv-p-0">
                        <div class="inv-tbl-wrap">
                            <table class="inv-tbl">
                                <thead>
                                    <tr>
                                        <th>{{ __('Warehouse') }}</th>
                                        <th class="inv-tbl-r">{{ __('Total Value') }}</th>
                                        <th class="inv-tbl-r">{{ __('Items') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($trackedProducts->groupBy(fn ($p) => $p->branch?->name ?? 'Default') as $warehouse => $products)
                                    <tr>
                                        <td>
                                            <div class="inv-flex-1">
                                                <svg class="inv-sec-ic-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                                                {{ $warehouse }}
                                            </div>
                                        </td>
                                        <td class="inv-numr">{{ number_format($products->sum('cost') ?? 0, 2) }}</td>
                                        <td class="inv-numr">{{ $products->count() }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="inv-empty">{{ __('No inventory data available.') }}</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="inv-tbl-total">
                                        <td><strong>{{ __('Grand Total') }}</strong></td>
                                        <td class="inv-numr"><strong>{{ number_format($trackedProducts->sum('cost') ?? 0, 2) }}</strong></td>
                                        <td class="inv-numr"><strong>{{ $trackedProducts->count() }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Low Stock Section --}}
                <div class="inv-card">
                    <div class="inv-card-h">
                        <svg class="inv-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span>{{ __('Low Stock Items') }}</span>
                    </div>
                    <div class="inv-card-body inv-p-0">
                        <div class="inv-tbl-wrap">
                            <table class="inv-tbl">
                                <thead>
                                    <tr>
                                        <th>{{ __('Product') }}</th>
                                        <th>{{ __('SKU') }}</th>
                                        <th class="inv-tbl-r">{{ __('Qty On Hand') }}</th>
                                        <th class="inv-tbl-r">{{ __('Reorder Point') }}</th>
                                        <th class="inv-tbl-r">{{ __('Shortage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lowStockItems as $item)
                                    <tr>
                                        <td>
                                            <div class="inv-flex-1">
                                                <a href="{{ route('accounting.inventory.items.show', $item) }}" class="inv-link">{{ $item->name }}</a>
                                                <div class="inv-sub">{{ $item->itemCategory?->name ?? '' }}</div>
                                            </div>
                                        </td>
                                        <td class="inv-mono">{{ $item->sku }}</td>
                                        <td class="inv-numr">{{ $item->stock_qty ?? 0 }}</td>
                                        <td class="inv-numr">{{ $item->reorder_point ?? 0 }}</td>
                                        <td class="inv-numr inv-numr-red">{{ ($item->reorder_point ?? 0) - ($item->stock_qty ?? 0) }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="inv-empty">{{ __('No low stock items. All products are adequately stocked.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="inv-rail">
                <div class="inv-rail-card">
                    <div class="inv-rail-sec">
                        <div class="inv-rail-sec-head">
                            <svg class="inv-rail-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            <span class="inv-rail-sec-label">{{ __('Quick Nav') }}</span>
                        </div>
                        <div class="inv-rail-rule"></div>
                        <a href="{{ route('accounting.invsetup.categories') }}" class="inv-rail-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                            {{ __('Categories') }}
                        </a>
                        <a href="{{ route('accounting.invsetup.assemblies') }}" class="inv-rail-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m7.08 7.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m7.08-7.08l4.24-4.24"/></svg>
                            {{ __('Assemblies') }}
                        </a>
                        <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-rail-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/></svg>
                            {{ __('Transfers & Adjustments') }}
                        </a>
                        <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-rail-item inv-rail-item-on">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                            {{ __('Valuation & Low Stock') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
