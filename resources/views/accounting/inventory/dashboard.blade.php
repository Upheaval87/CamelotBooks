<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-head">
            <div>
                <h1>{{ __('Inventory Dashboard') }}</h1>
                <div class="inv-sub">{{ __('Overview of your inventory health and movement.') }}</div>
            </div>
            <div style="display:flex;gap:8px">
                <a href="{{ route('accounting.inventory.items.create') }}" class="inv-btn inv-btn-cta">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('Add Item') }}
                </a>
            </div>
        </div>

        <div class="inv-kpis">
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Total Items') }}</div>
                <div class="inv-kpi-v">{{ number_format($totalProducts) }}</div>
                <div class="inv-kpi-sub">{{ $trackedProducts }} {{ __('tracked') }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Categories') }}</div>
                <div class="inv-kpi-v">{{ number_format($categories) }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Inventory Value') }}</div>
                <div class="inv-kpi-v">{{ number_format($valuationTotal, 2) }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Low Stock Alerts') }}</div>
                <div class="inv-kpi-v" style="color:{{ $lowStockCount > 0 ? 'var(--red-2, #b91c1c)' : 'var(--ink)' }}">{{ $lowStockCount }}</div>
                @if($lowStockCount > 0)
                <a href="{{ route('accounting.invsetup.lowstock') }}" class="inv-kpi-sub" style="color:var(--red-2,#b91c1c);text-decoration:underline">{{ __('View items below reorder point') }}</a>
                @endif
            </div>
        </div>

        <div class="inv-shell">
            <div>
                <div class="inv-card">
                    <div class="inv-card-head">
                        <h2>{{ __('Inventory Movement') }}</h2>
                    </div>
                    @if($movementData->isEmpty())
                        <div class="inv-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                            <p>{{ __('No inventory movements yet.') }}</p>
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
                                <div class="inv-bar-val">{{ number_format($row->total_debit, 0) }}</div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="inv-card">
                    <div class="inv-card-head">
                        <h2>{{ __('Recent Adjustments') }}</h2>
                        <a href="{{ route('accounting.invsetup.stockcount') }}" class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('Stock Count') }}</a>
                <a href="{{ route('accounting.invsetup.categories') }}" class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('Categories') }}</a>
                <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('Valuation') }}</a>
                    </div>
                    @if($recentAdjustments->isEmpty())
                        <div class="inv-empty">
                            <p>{{ __('No recent adjustments.') }}</p>
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

            <div class="inv-rail">
                <div class="inv-rail-card">
                    <h3>{{ __('Quick Nav') }}</h3>
                    <a href="{{ route('accounting.inventory.items') }}" class="inv-rail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                        {{ __('All Items') }}
                    </a>
                    <a href="{{ route('accounting.inventory.items') }}" class="inv-rail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                        {{ __('All Items') }}
                    </a>
                    <a href="{{ route('accounting.invsetup.categories') }}" class="inv-rail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                        {{ __('Categories') }}
                    </a>
                    <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-rail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/></svg>
                        {{ __('Transfers & Adjustments') }}
                    </a>
                    <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-rail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                        {{ __('Valuation & Low Stock') }}
                    </a>
                </div>

                <div class="inv-rail-card">
                    <h3>{{ __('Top Items') }}</h3>
                    @foreach($topItems as $item)
                    <div class="inv-mov-row">
                        <div class="inv-mov-icon" style="background:rgba(18,143,142,.08);color:var(--sec,#128F8E)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                        </div>
                        <div class="inv-mov-info">
                            <div class="inv-mov-title">{{ $item->name }}</div>
                            <div class="inv-mov-sub">{{ $item->sku }}</div>
                        </div>
                        <div class="inv-mov-amt">{{ number_format($item->sales_price ?? 0, 2) }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
