@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
@endphp
<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-head">
            <div>
                <h1>{{ __('Inventory Dashboard') }}</h1>
                <div class="inv-sub">{{ __('Overview of your inventory health and movement.') }}</div>
            </div>
            <div style="display:flex;gap:8px">
                <a href="{{ route('accounting.inventory.items') }}" class="inv-btn inv-btn-ghost">
                    {{ __('View All Items') }}
                </a>
                <a href="{{ route('accounting.inventory.items.create') }}" class="inv-btn inv-btn-cta">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('Add Item') }}
                </a>
            </div>
        </div>

        <div class="inv-kpis">
            <div class="inv-kpi hero">
                <div class="inv-kpi-l">{{ __('Total Items') }}</div>
                <div class="inv-kpi-v">{{ number_format($totalProducts) }}</div>
                <div class="inv-kpi-n">{{ $trackedProducts }} {{ __('tracked') }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Categories') }}</div>
                <div class="inv-kpi-v">{{ number_format($categories) }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Inventory Value') }}</div>
                <div class="inv-kpi-v">{{ $cs }}{{ number_format($valuationTotal, 2) }}</div>
            </div>
            <div class="inv-kpi" @if($lowStockCount > 0) style="border-color:rgba(185,28,28,.3)" @endif>
                <div class="inv-kpi-l">{{ __('Low Stock Alerts') }}</div>
                <div class="inv-kpi-v" style="color:{{ $lowStockCount > 0 ? 'var(--red-2, #b91c1c)' : 'var(--ink)' }}">{{ $lowStockCount }}</div>
                @if($lowStockCount > 0)
                <a href="{{ route('accounting.invsetup.lowstock') }}" class="inv-kpi-n" style="color:var(--red-2,#b91c1c);text-decoration:underline">{{ __('View items below reorder point') }}</a>
                @endif
            </div>
        </div>

        <div class="inv-card">
            <div class="inv-card-h">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                </div>
                {{ __('Inventory Movement') }}
            </div>
            <div class="inv-card-body" style="padding:20px">
                @if($movementData->isEmpty())
                    <div class="inv-empty">
                        <div class="inv-empty-ic">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                        </div>
                        <p>{{ __('No inventory movements yet.') }}</p>
                        <div class="inv-empty-sub">{{ __('Start by creating items and recording stock adjustments.') }}</div>
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

        <div class="inv-card">
            <div class="inv-card-h">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
                </div>
                {{ __('Recent Adjustments') }}
                <div class="inv-sec-ic-inv" style="display:flex;gap:6px">
                    <a href="{{ route('accounting.invsetup.stockcount') }}" class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('Stock Count') }}</a>
                    <a href="{{ route('accounting.invsetup.categories') }}" class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('Categories') }}</a>
                    <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('Valuation') }}</a>
                </div>
            </div>
            @if($recentAdjustments->isEmpty())
                <div style="padding:20px">
                    <div class="inv-empty">
                        <div class="inv-empty-ic">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
                        </div>
                        <p>{{ __('No recent adjustments.') }}</p>
                        <div class="inv-empty-sub">{{ __('Adjustments will appear here once you record stock counts or transfers.') }}</div>
                    </div>
                </div>
            @else
                <div class="inv-tbl-wrap">
                    <table class="inv-tbl">
                        <thead><tr><th>{{ __('Reference') }}</th><th>{{ __('Status') }}</th><th>{{ __('Date') }}</th></tr></thead>
                        <tbody>
                        @foreach($recentAdjustments as $adj)
                        <tr>
                            <td><a href="#" class="inv-link">{{ $adj->adjustment_number ?? $adj->id }}</a></td>
                            <td><span class="inv-badge inv-badge-{{ $adj->status === 'posted' ? 'active' : 'warning' }}">{{ ucfirst($adj->status) }}</span></td>
                            <td>{{ $adj->created_at?->format('M d, Y') }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
