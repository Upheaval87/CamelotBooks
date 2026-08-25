@extends('layouts.pos-mobile', ['title' => 'Sale ' . $sale->sale_number])

@section('content')
<div class="pos-m-page pos-m-receipt-page">

    {{-- §9.2 — Success Header --}}
    <div class="pos-m-receipt-header">
        <div class="pos-m-receipt-check">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="pos-m-receipt-title">Sale Complete</div>
        <div class="pos-m-receipt-number">{{ $sale->sale_number }}</div>
    </div>

    {{-- §9.3 — Branded Receipt Document --}}
    <div class="pos-m-receipt-doc">
        {{-- Header --}}
        <div class="pos-m-receipt-doc-header">
            <div class="pos-m-receipt-brand">
                <div class="pos-m-receipt-cb">CB</div>
            </div>
            <div class="pos-m-receipt-company">{{ $company?->name ?? 'CamelotBooks' }}</div>
        </div>

        {{-- Meta grid --}}
        <div class="pos-m-receipt-meta">
            <div class="pos-m-receipt-meta-row">
                <span>Receipt #</span>
                <strong>{{ $sale->sale_number }}</strong>
            </div>
            <div class="pos-m-receipt-meta-row">
                <span>Date</span>
                <span>{{ $sale->created_at->format('d M Y, H:i') }}</span>
            </div>
            <div class="pos-m-receipt-meta-row">
                <span>Cashier</span>
                <span>{{ $sale->cashier_name ?? auth()->user()->name ?? '—' }}</span>
            </div>
            @if($sale->customer)
            <div class="pos-m-receipt-meta-row">
                <span>Customer</span>
                <span>{{ $sale->customer->name }}</span>
            </div>
            @endif
            @if($sale->terminal)
            <div class="pos-m-receipt-meta-row">
                <span>Terminal</span>
                <span>{{ $sale->terminal->identifier }}</span>
            </div>
            @endif
        </div>

        <div class="pos-m-receipt-divider"></div>

        {{-- Line items --}}
        <table class="pos-m-receipt-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="pos-m-receipt-td-r">Qty</th>
                    <th class="pos-m-receipt-td-r">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->lines as $line)
                    <tr>
                        <td>
                            <div class="pos-m-receipt-item-name">{{ $line->product?->name ?? 'Unknown' }}</div>
                            <div class="pos-m-receipt-item-meta">{{ $line->product?->sku }} @ K {{ number_format($line->unit_price, 2) }}</div>
                        </td>
                        <td class="pos-m-receipt-td-r">{{ number_format($line->quantity, 2) }}</td>
                        <td class="pos-m-receipt-td-r">K {{ number_format($line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pos-m-receipt-divider"></div>

        {{-- Totals --}}
        <div class="pos-m-receipt-totals">
            @php
                $subtotal = $sale->lines->sum(fn($l) => $l->quantity * $l->unit_price);
                $discount = $sale->lines->sum(fn($l) => $l->discount_amount ?? 0);
                $tax = $sale->lines->sum(fn($l) => $l->tax_amount ?? 0);
            @endphp
            <div class="pos-m-receipt-total-row">
                <span>Subtotal</span>
                <span>K {{ number_format($subtotal, 2) }}</span>
            </div>
            @if($discount > 0)
                <div class="pos-m-receipt-total-row">
                    <span>Discount</span>
                    <span>−K {{ number_format($discount, 2) }}</span>
                </div>
            @endif
            @if($tax > 0)
                <div class="pos-m-receipt-total-row">
                    <span>Tax</span>
                    <span>K {{ number_format($tax, 2) }}</span>
                </div>
            @endif
            <div class="pos-m-receipt-total-row pos-m-receipt-total-row--grand">
                <span>Total</span>
                <span>K {{ number_format($sale->total, 2) }}</span>
            </div>
        </div>

        {{-- Payment info --}}
        @if($sale->payments->isNotEmpty())
            <div class="pos-m-receipt-divider"></div>
            <div class="pos-m-receipt-payments-title">Payment</div>
            @foreach($sale->payments as $payment)
                <div class="pos-m-receipt-payment-row">
                    <span>{{ $payment->paymentMethod?->name ?? 'Unknown' }}</span>
                    <span>K {{ number_format($payment->amount, 2) }}</span>
                </div>
                @if($payment->cash_tendered > 0)
                    <div class="pos-m-receipt-payment-detail">
                        <span>Cash tendered</span>
                        <span>K {{ number_format($payment->cash_tendered, 2) }}</span>
                    </div>
                    <div class="pos-m-receipt-payment-detail">
                        <span>Change</span>
                        <span>K {{ number_format($payment->change_given, 2) }}</span>
                    </div>
                @endif
                @if($payment->reference_number)
                    <div class="pos-m-receipt-payment-detail">
                        <span>Ref #</span>
                        <span>{{ $payment->reference_number }}</span>
                    </div>
                @endif
            @endforeach
        @endif

        <div class="pos-m-receipt-divider"></div>

        {{-- Footer --}}
        <div class="pos-m-receipt-footer">
            Thank you for your purchase!<br>
            {{ $company?->name ?? 'CamelotBooks' }} · {{ $company?->email ?? '' }}
        </div>
    </div>

    {{-- §9.4 — Action Buttons --}}
    <div class="pos-m-receipt-actions">
        <button onclick="window.print()" class="pos-m-btn pos-m-btn--ghost pos-m-btn--block">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Receipt
        </button>
        <a href="{{ route('pos.m.sell') }}" class="pos-m-btn pos-m-btn--solid pos-m-btn--block">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/></svg>
            New Sale
        </a>
    </div>

    {{-- Quick Nav --}}
    <div class="pos-m-receipt-nav">
        <a href="{{ route('pos.m.home') }}">← Home</a>
        <a href="{{ route('pos.receipts.index') }}">All Receipts →</a>
    </div>

</div>

@include('pos.mobile._bottom-nav', ['active' => 'receipts'])
@endsection
