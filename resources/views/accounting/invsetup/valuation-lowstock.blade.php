@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $lowStockCount = $lowStockItems->count();
    $outCount = $lowStockItems->filter(fn($p) => ($p->stock_qty ?? $p->stock_on_hand ?? 0) <= 0)->count();
@endphp
<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-head">
            <div>
                <h1>{{ __('Valuation & Low Stock') }}</h1>
                <div class="inv-sub">{{ __('Inventory value by warehouse and items needing attention.') }}</div>
            </div>
            <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button">{{ __('Export CSV') }}</button>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'valuation'])

        <div class="inv-kpis">
            <div class="inv-kpi hero">
                <div class="inv-kpi-l">{{ __('Total Inventory Value') }}</div>
                <div class="inv-kpi-v">{{ $cs }}{{ number_format($valuationTotal ?? 0, 0) }}</div>
                <div class="inv-kpi-n">{{ __('all warehouses') }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Tracked Items') }}</div>
                <div class="inv-kpi-v">{{ number_format($itemsValued) }}</div>
                <div class="inv-kpi-n">{{ __('with cost data') }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Warehouses') }}</div>
                <div class="inv-kpi-v">{{ number_format($warehouseCount) }}</div>
                <div class="inv-kpi-n">{{ __('active locations') }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Low / Out Items') }}</div>
                <div class="inv-kpi-v">{{ $lowStockCount }}</div>
                <div class="inv-kpi-n inv-kpi-n-red">{{ __('need reorder') }}</div>
            </div>
        </div>

        <div class="inv-card mb">
            <div class="inv-sec-head">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                </div>
                <h2>{{ __('Valuation by Warehouse') }}</h2>
            </div>
            <div class="inv-tbl-wrap">
                <table class="inv-tbl">
                    <thead>
                        <tr>
                            <th>{{ __('Warehouse') }}</th>
                            <th class="num">{{ __('Items') }}</th>
                            <th class="num">{{ __('On Hand') }}</th>
                            <th class="num">{{ __('Value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trackedProducts->groupBy(fn($p) => $p->itemCategory?->name ?? __('General')) as $group => $items)
                        @php
                            $totalQty = $items->sum(fn($p) => $p->stock_qty ?? 0);
                            $totalVal = $items->sum(fn($p) => ($p->stock_qty ?? 0) * ($p->sales_price ?? 0));
                        @endphp
                        <tr>
                            <td style="font-weight:600;color:var(--ink)">{{ $group }}</td>
                            <td class="num">{{ $items->count() }}</td>
                            <td class="num">{{ number_format($totalQty) }}</td>
                            <td class="num">{{ number_format($totalVal, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4">
                                <div class="inv-empty">
                                    <div class="inv-empty-ic">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                                    </div>
                                    <p>{{ __('No items tracked.') }}</p>
                                    <div class="inv-empty-sub">{{ __('Add inventory items and enable tracking to see valuation data.') }}</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="inv-card">
            <div class="inv-sec-head">
                <div class="inv-sec-ic" style="background:linear-gradient(135deg, var(--amber-2, #b45309), #d97706); color:#fff">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <h2>{{ __('Low Stock') }}</h2>
                <span class="inv-rule"></span>
                <div class="right" style="margin-left:auto">
                    <a href="{{ route('accounting.purchase-orders.create') }}" class="inv-btn inv-btn-ghost inv-btn-sm" style="color:var(--sec);background:rgba(18,143,142,.08);border-color:rgba(18,143,142,.3)">{{ __('Create Purchase Order') }}</a>
                </div>
            </div>
            <div class="inv-tbl-wrap">
                <table class="inv-tbl">
                    <thead>
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('SKU') }}</th>
                            <th class="num">{{ __('On Hand') }}</th>
                            <th class="num">{{ __('Reorder At') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lowStockItems as $item)
                        @php
                            $qty = $item->stock_qty ?? $item->stock_on_hand ?? 0;
                            $isOut = $qty <= 0;
                        @endphp
                        <tr>
                            <td style="font-weight:600;color:var(--ink)">{{ $item->name }}</td>
                            <td class="inv-mono">{{ $item->sku }}</td>
                            <td class="num">{{ number_format($qty) }}</td>
                            <td class="num">{{ number_format($item->reorder_point) }}</td>
                            <td>
                                @if($isOut)
                                <span class="inv-badge inv-badge-danger"><span class="inv-badge-dot"></span>{{ __('Out') }}</span>
                                @else
                                <span class="inv-badge inv-badge-warning"><span class="inv-badge-dot"></span>{{ __('Low') }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">
                                <div class="inv-empty">
                                    <div class="inv-empty-ic inv-empty-ic-ok">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    </div>
                                    <p>{{ __('All good — no items below reorder point.') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
