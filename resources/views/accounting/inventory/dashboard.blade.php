@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
@endphp
<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-head">
            <div>
                <h1>{{ __('Inventory Centre') }}</h1>
                <div class="inv-sub">{{ __('Products, stock, pricing, costing and movements — connected to Sales, Purchasing and the GL.') }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="{{ route('accounting.inventory.items') }}" class="inv-btn inv-btn-ghost inv-btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    {{ __('View Reports') }}
                </a>
                <a href="{{ route('accounting.inventory.items.export') }}" class="inv-btn inv-btn-ghost inv-btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    {{ __('Export Items') }}
                </a>
                <a href="{{ route('accounting.inventory.items.create') }}" class="inv-btn inv-btn-cta inv-btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('Add Item') }}
                </a>
            </div>
        </div>

        {{-- KPI strip --}}
        <div class="inv-kpis">
            <div class="inv-kpi hero">
                <div class="inv-kpi-l">{{ __('Total Inventory Value') }}</div>
                <div class="inv-kpi-v">{{ $cs }}{{ number_format($valuationTotal, 2) }}</div>
                <div class="inv-kpi-n" style="color:#dff7f6">{{ __('Weighted average valuation') }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Total Items') }}</div>
                <div class="inv-kpi-v">{{ number_format($totalProducts) }}</div>
                <div class="inv-kpi-n">{{ $trackedProducts }} {{ __('tracked') }}</div>
            </div>
            <div class="inv-kpi warn">
                <div class="inv-kpi-l">{{ __('Low Stock') }}</div>
                <div class="inv-kpi-v">{{ $lowStockCount }}</div>
                @if($lowStockCount > 0)
                <div class="inv-kpi-n"><a href="{{ route('accounting.invsetup.lowstock') }}" style="color:var(--amber-2,#b45309);text-decoration:underline">{{ __('Reorder suggestions') }} &rarr;</a></div>
                @else
                <div class="inv-kpi-n">{{ __('All items well stocked') }}</div>
                @endif
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Categories') }}</div>
                <div class="inv-kpi-v">{{ number_format($categories) }}</div>
                <div class="inv-kpi-n">{{ __('Active categories') }}</div>
            </div>
        </div>

        {{-- Inventory Movement --}}
        <div class="inv-card">
            <div class="inv-sec-head">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                </div>
                <h2>{{ __('Inventory Movement') }}</h2>
                <span class="inv-rule"></span>
            </div>
            <div class="inv-card-body" style="padding:20px">
                @if($movementData->isEmpty())
                    <div class="inv-empty" style="padding:40px 20px">
                        <div class="inv-empty-ic">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                        </div>
                        <p>{{ __('No stock movements recorded yet.') }}</p>
                        <div class="inv-empty-sub" style="margin-top:12px;display:flex;gap:10px;justify-content:center">
                            <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-btn inv-btn-ghost inv-btn-sm">{{ __('Record Transfer') }}</a>
                            <a href="{{ route('accounting.invsetup.stockcount') }}" class="inv-btn inv-btn-ghost inv-btn-sm">{{ __('Record Adjustment') }}</a>
                        </div>
                    </div>
                @else
                    <div class="inv-bars">
                        @php($maxMov = $movementData->max(fn($r) => max($r->total_debit, $r->total_credit)) ?: 1)
                        @foreach($movementData as $row)
                        <div class="inv-bar-row">
                            <div class="inv-bar-label" title="{{ $row->product_name }}">{{ $row->product_name }}</div>
                            <div class="inv-bar-track">
                                <div class="inv-bar-fill" style="width:{{ round(($row->total_debit / $maxMov) * 100) }}%"></div>
                            </div>
                            <div class="inv-bar-val">{{ $cs }}{{ number_format($row->total_debit, 0) }}</div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Adjustments --}}
        <div class="inv-card">
            <div class="inv-sec-head">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
                </div>
                <h2>{{ __('Recent Adjustments') }}</h2>
                <span class="inv-rule"></span>
                <div style="display:flex;gap:6px;margin-left:auto">
                    <a href="{{ route('accounting.invsetup.stockcount') }}" class="inv-btn inv-btn-ghost inv-btn-xs">{{ __('Stock Count') }}</a>
                    <a href="{{ route('accounting.invsetup.categories') }}" class="inv-btn inv-btn-ghost inv-btn-xs">{{ __('Categories') }}</a>
                    <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-btn inv-btn-ghost inv-btn-xs">{{ __('Valuation') }}</a>
                </div>
            </div>
            @if($recentAdjustments->isEmpty())
                <div class="inv-empty" style="padding:40px 20px">
                    <div class="inv-empty-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
                    </div>
                    <p>{{ __('No recent adjustments.') }}</p>
                    <div class="inv-empty-sub">{{ __('Adjustments will appear here once you record stock counts or transfers.') }}</div>
                </div>
            @else
                <div class="inv-tbl-wrap">
                    <table class="inv-tbl">
                        <thead><tr><th>{{ __('Reference') }}</th><th>{{ __('Status') }}</th><th>{{ __('Date') }}</th></tr></thead>
                        <tbody>
                        @foreach($recentAdjustments as $adj)
                        <tr>
                            <td><a href="{{ route('accounting.invsetup.adjustments') }}" class="inv-link">{{ $adj->adjustment_number ?? $adj->id }}</a></td>
                            <td><span class="inv-badge inv-badge-{{ $adj->status === 'posted' ? 'active' : 'warning' }}"><span class="inv-badge-dot"></span>{{ ucfirst($adj->status) }}</span></td>
                            <td class="em">{{ $adj->created_at?->format('M d, Y') }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
