@extends('layouts.pos-mobile', ['title' => 'Register & Shift'])

@section('content')
<div class="pos-m-page" style="padding-bottom: 5rem;">

    {{-- §11 — Header --}}
    <div class="pos-m-greeting">
        <div class="pos-m-greeting-name">Register & Shift</div>
        <div class="pos-m-greeting-sub">
            {{ $terminal?->identifier ?? 'No terminal' }}
            @if($terminal?->branch) · {{ $terminal->branch->name }} @endif
        </div>
    </div>

    {{-- §11 — Shift card --}}
    <div class="pos-m-section-card">
        <div class="pos-m-section-head">
            <div class="pos-m-section-icon pos-m-section-icon--solid">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <div class="pos-m-section-title">Shift Status</div>
                <div class="pos-m-section-sub">Opened {{ now()->format('H:i') }} · {{ auth()->user()->name ?? 'Cashier' }}</div>
            </div>
            <span class="pos-m-badge pos-m-badge--active">Open</span>
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
        </div>
    </div>

    {{-- §11 — Cash count --}}
    <div class="pos-m-section-card">
        <div class="pos-m-section-head">
            <div class="pos-m-section-icon pos-m-section-icon--solid">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="pos-m-section-title">Cash Summary</div>
        </div>

        <div class="pos-m-summary-row">
            <span>Cash in (sales)</span>
            <span class="pos-m-summary-pos">+K {{ number_format($cashSales, 2) }}</span>
        </div>
        <div class="pos-m-summary-row">
            <span>Card sales</span>
            <span class="pos-m-summary-neutral">K {{ number_format($cardSales, 2) }}</span>
        </div>
        <div class="pos-m-summary-row">
            <span>Mobile Money sales</span>
            <span class="pos-m-summary-neutral">K {{ number_format($mobileSales, 2) }}</span>
        </div>
        <div class="pos-m-divider"></div>
        <div class="pos-m-summary-row pos-m-summary-row--total">
            <span>Total Revenue</span>
            <span>K {{ number_format($totalRevenue, 2) }}</span>
        </div>
    </div>

    {{-- §11 — Actions --}}
    <div class="pos-m-action-cards">
        <a href="javascript:window.print()" class="pos-m-action-card">
            <div class="pos-m-action-ic pos-m-action-ic--history">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            </div>
            <div>
                <div class="pos-m-action-card-title">Print X-Report</div>
                <div class="pos-m-action-card-sub">Current shift summary</div>
            </div>
        </a>

        <div class="pos-m-action-card pos-m-action-card--danger"
            x-data="{ showConfirm: false }">
            <button class="pos-m-action-card-inner" @click="showConfirm = true">
                <div class="pos-m-action-ic pos-m-action-ic--return">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <div class="pos-m-action-card-title">Close Shift · Print Z-Report</div>
                    <div class="pos-m-action-card-sub">Requires identity verification</div>
                </div>
            </button>
        </div>
    </div>

    <div class="pos-m-section-note">
        Closing a shift will print the Z-Report and reconcile all cash in the drawer.
        Over/short variance will be posted to the general ledger.
    </div>

</div>

@include('pos.mobile._bottom-nav', ['active' => 'home'])
@endsection
