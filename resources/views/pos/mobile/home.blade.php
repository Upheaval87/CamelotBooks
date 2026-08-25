@extends('layouts.pos-mobile', ['title' => 'POS Home'])

@section('content')
<div class="pos-m-page" style="padding-bottom: 5rem;">

    {{-- §6.2 — Greeting --}}
    <div class="pos-m-greeting">
        <div class="pos-m-greeting-name">{{ $cashierName }}</div>
        <div class="pos-m-greeting-sub">
            {{ $terminal ? $terminal->identifier : 'No terminal' }}
            @if($terminal?->branch) · {{ $terminal->branch->name }} @endif
        </div>
    </div>

    {{-- §6.2 — Summary Strip (3 stats) --}}
    <div class="pos-m-stats">
        <div class="pos-m-stat">
            <div class="pos-m-stat-val">{{ $todayCount }}</div>
            <div class="pos-m-stat-lbl">Sales today</div>
        </div>
        <div class="pos-m-stat">
            <div class="pos-m-stat-val">K {{ number_format($todayRevenue, 2) }}</div>
            <div class="pos-m-stat-lbl">Revenue today</div>
        </div>
        <div class="pos-m-stat">
            <div class="pos-m-stat-val">K {{ number_format($todayRevenue / max($todayCount, 1), 2) }}</div>
            <div class="pos-m-stat-lbl">Avg. ticket</div>
        </div>
    </div>

    {{-- §6.3 — Quick Actions --}}
    <div class="pos-m-section-title">Quick Actions</div>
    <div class="pos-m-actions">
        <a href="{{ route('pos.m.sell') }}" class="pos-m-action-btn">
            <div class="pos-m-action-ic pos-m-action-ic--sell">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/></svg>
            </div>
            <span>New Sale</span>
        </a>
        <a href="{{ route('pos.m.checkout') }}" class="pos-m-action-btn">
            <div class="pos-m-action-ic pos-m-action-ic--checkout">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            </div>
            <span>Checkout</span>
        </a>
        <a href="{{ route('pos.m.ret-intake') }}" class="pos-m-action-btn">
            <div class="pos-m-action-ic pos-m-action-ic--return">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
            </div>
            <span>Returns</span>
        </a>
        <a href="{{ route('pos.receipts.index') }}" class="pos-m-action-btn">
            <div class="pos-m-action-ic pos-m-action-ic--history">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span>History</span>
        </a>
    </div>

    {{-- §6.4 — Recent Activity --}}
    <div class="pos-m-section-title">Recent Sales</div>
    @if($recentSales->isEmpty())
        <div class="pos-m-empty">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p>No sales yet today.</p>
        </div>
    @else
        <div class="pos-m-recent-list">
            @foreach($recentSales as $sale)
                <a href="{{ route('pos.m.receipt', $sale->id) }}" class="pos-m-recent-item">
                    <div class="pos-m-recent-info">
                        <div class="pos-m-recent-num">{{ $sale->sale_number }}</div>
                        <div class="pos-m-recent-meta">
                            {{ $sale->customer?->name ?? 'Walk-in' }} · {{ $sale->created_at->format('H:i') }}
                        </div>
                    </div>
                    <div class="pos-m-recent-right">
                        <div class="pos-m-recent-total">K {{ number_format($sale->total, 2) }}</div>
                        <div class="pos-m-recent-method">
                            {{ $sale->payments->first()?->paymentMethod?->name ?? '—' }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

</div>

@include('pos.mobile._bottom-nav', ['active' => 'home'])
@endsection
