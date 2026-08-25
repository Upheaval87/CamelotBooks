@extends('layouts.pos-mobile', ['title' => 'POS Home'])

@section('content')
<div class="pos-m-page" style="position:relative;display:flex;flex-direction:column;min-height:100dvh">

    {{-- Topbar --}}
    <div class="pos-m-topbar">
        <span class="pos-m-topbar-mark">C</span>
        <div class="pos-m-topbar-brand">
            <div class="pos-m-topbar-name">CamelotBooks</div>
            <div class="pos-m-topbar-sub">Point of Sale</div>
        </div>
        <div class="pos-m-topbar-right">
            <span class="pos-m-topbar-av">{{ strtoupper(substr($cashierName ?? 'U', 0, 2)) }}</span>
        </div>
    </div>

    {{-- Scrollable content --}}
    <div style="flex:1;overflow-y:auto;padding:.375rem 1.25rem 5.5rem;scrollbar-width:none">
        {{-- Greeting --}}
        <div class="pos-m-greet">Good {{ now()->format('A') === 'AM' ? 'morning' : 'afternoon' }}, {{ strtok($cashierName ?? 'User', ' ') }}</div>
        <div class="pos-m-greet-sub">
            {{ $terminal ? $terminal->name ?? $terminal->identifier : 'No register' }} · {{ $terminal ? $terminal->identifier : '' }} · Shift open since {{ now()->format('H:i') }}
        </div>

        {{-- Summary strip --}}
        <div class="pos-m-card pos-m-sum" style="margin-top:.875rem">
            <div class="pos-m-c">
                <div class="pos-m-sum-l">Today's Sales</div>
                <div class="pos-m-sum-v">K {{ number_format($todayRevenue, 0) }}</div>
                <div class="pos-m-sum-d" style="color:var(--green,#1B7F4D)">▲ 12%</div>
            </div>
            <div class="pos-m-c">
                <div class="pos-m-sum-l">Transactions</div>
                <div class="pos-m-sum-v">{{ $todayCount }}</div>
                <div class="pos-m-sum-d" style="color:#9AAEAE">{{ $voidCount ?? 0 }} void</div>
            </div>
            <div class="pos-m-c">
                <div class="pos-m-sum-l">Outstanding</div>
                <div class="pos-m-sum-v">K {{ number_format($outstanding ?? 0, 0) }}</div>
                <div class="pos-m-sum-d" style="color:var(--red,#C2453F)">{{ $creditCount ?? 0 }} credit</div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="pos-m-sect">
            <span class="pos-m-sect-t">Quick Actions</span>
        </div>
        <div class="pos-m-card pos-m-qa">
            <a href="{{ route('pos.m.receipts') }}" class="pos-m-qa-a">
                <span class="pos-m-qa-i">📋</span>
                Receipts
            </a>
            <a href="{{ route('pos.m.register') }}" class="pos-m-qa-a">
                <span class="pos-m-qa-i">🗄️</span>
                Register
            </a>
            <a href="{{ route('pos.m.ret-intake') }}" class="pos-m-qa-a">
                <span class="pos-m-qa-i">⏱️</span>
                Shifts
            </a>
            <a href="{{ route('pos.m.products') }}" class="pos-m-qa-a">
                <span class="pos-m-qa-i">📦</span>
                Products
            </a>
        </div>

        {{-- Recent Activity --}}
        <div class="pos-m-sect">
            <span class="pos-m-sect-t">Recent Activity</span>
            <a href="{{ route('pos.m.receipts') }}" class="pos-m-sect-a">All</a>
        </div>
        @if(isset($recentSales) && $recentSales->count())
            <div class="pos-m-card pos-m-list">
                @foreach($recentSales->take(5) as $sale)
                    <a href="{{ route('pos.m.receipt', $sale->id) }}" class="pos-m-r">
                        <div class="pos-m-list-n">
                            Sale {{ $sale->sale_number }}
                            <small>{{ $sale->customer?->name ?? 'Walk-in' }} · {{ $sale->payments->first()?->paymentMethod?->name ?? '—' }}</small>
                        </div>
                        <span class="pos-m-list-amt" @if($sale->total < 0) style="color:#C2453F" @endif>K {{ number_format(abs($sale->total), 0) }}</span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="pos-m-empty-inline">No sales yet today</div>
        @endif
    </div>

    @include('pos.mobile._bottom-nav', ['active' => 'home'])
</div>
@endsection
