@extends('layouts.pos-mobile', ['title' => 'Register'])

@section('content')
<div class="pos-m-page" style="padding-bottom:5.5rem">

    {{-- Header --}}
    <div class="pos-m-greeting">
        <div class="pos-m-greeting-name">Register</div>
        <div class="pos-m-greeting-sub">
            {{ $terminal ? $terminal->name . ' · ' . $terminal->identifier : 'No register assigned' }}
        </div>
    </div>

    {{-- Shift Status --}}
    <div class="pos-m-section-card">
        <div class="pos-m-section-head">
            <div class="pos-m-section-icon pos-m-section-icon--solid">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="pos-m-section-title">Shift Status</div>
                <div class="pos-m-section-sub">{{ now()->format('D d M Y') }}</div>
            </div>
        </div>
        <div class="pos-m-kv-grid">
            <div class="pos-m-kv">
                <span class="pos-m-kv-l">Terminal</span>
                <span class="pos-m-kv-v" style="font-size:.875rem">{{ $terminal?->identifier ?? '—' }}</span>
            </div>
            <div class="pos-m-kv">
                <span class="pos-m-kv-l">Status</span>
                <span class="pos-m-kv-v" style="font-size:.875rem;color:#1B7F4D">Open</span>
            </div>
        </div>
    </div>

    {{-- Cash Summary --}}
    <div class="pos-m-section-card">
        <div class="pos-m-section-head">
            <div class="pos-m-section-icon pos-m-section-icon--solid">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <div class="pos-m-section-title">Cash Summary</div>
                <div class="pos-m-section-sub">{{ now()->format('D d M Y') }}</div>
            </div>
        </div>

        <div class="pos-m-kv-grid">
            <div class="pos-m-kv">
                <span class="pos-m-kv-l">Receipts</span>
                <span class="pos-m-kv-v">{{ $receiptCount }}</span>
            </div>
            <div class="pos-m-kv">
                <span class="pos-m-kv-l">Total Revenue</span>
                <span class="pos-m-kv-v">K {{ number_format($totalRevenue, 2) }}</span>
            </div>
            <div class="pos-m-kv">
                <span class="pos-m-kv-l">Cash Sales</span>
                <span class="pos-m-kv-v">K {{ number_format($cashSales, 2) }}</span>
            </div>
            <div class="pos-m-kv">
                <span class="pos-m-kv-l">Card Sales</span>
                <span class="pos-m-kv-v">K {{ number_format($cardSales, 2) }}</span>
            </div>
        </div>

        <hr class="pos-m-divider" style="margin:.75rem 0">

        <div class="pos-m-summary-row">
            <span>Mobile Sales</span>
            <span class="pos-m-summary-neutral">K {{ number_format($mobileSales, 2) }}</span>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="pos-m-section-title">Actions</div>
    <div class="pos-m-action-cards">
        <a href="{{ route('pos.m.sell') }}" class="pos-m-action-card">
            <div class="pos-m-section-icon pos-m-section-icon--solid" style="background:rgba(14,110,103,.08);color:#0E6E67">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/></svg>
            </div>
            <div>
                <div class="pos-m-action-card-title">Start Selling</div>
                <div class="pos-m-action-card-sub">Open the sales screen</div>
            </div>
        </a>
        <a href="{{ route('pos.m.receipts') }}" class="pos-m-action-card">
            <div class="pos-m-section-icon" style="background:#EEF3F1;color:#5F7476">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <div class="pos-m-action-card-title">View Receipts</div>
                <div class="pos-m-action-card-sub">Today's transactions</div>
            </div>
        </a>
        <a href="{{ route('pos.m.ret-register') }}" class="pos-m-action-card">
            <div class="pos-m-section-icon" style="background:rgba(169,118,27,.08);color:#A9761B">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
            </div>
            <div>
                <div class="pos-m-action-card-title">Bottle Returns</div>
                <div class="pos-m-action-card-sub">View BRR register</div>
            </div>
        </a>
    </div>

    @include('pos.mobile._bottom-nav', ['active' => 'home'])
</div>
@endsection
