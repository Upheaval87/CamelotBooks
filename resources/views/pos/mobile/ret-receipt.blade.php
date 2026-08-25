@extends('layouts.pos-mobile', ['title' => 'BRR Receipt'])

@section('content')
<div class="pos-m-page" style="padding-bottom:5rem;">

    {{-- §14.2 — Back --}}
    <a href="{{ route('pos.m.ret-register') }}" class="pos-m-back-link">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Register
    </a>

    @if(session('success'))
        <div class="pos-m-toast pos-m-toast--success" style="position:static;transform:none;width:100%;margin-bottom:.75rem;">
            {{ session('success') }}
            <button class="pos-m-toast-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    @endif

    {{-- §14.2 — Branded Receipt --}}
    <div class="pos-m-receipt-card">

        {{-- Header --}}
        <div class="pos-m-receipt-hdr">
            <div class="pos-m-receipt-brand">CamelotBooks</div>
            <div class="pos-m-receipt-title">BOTTLE RETURN RECEIPT</div>
            <div class="pos-m-receipt-num">BRR-{{ $returnable->brr_number }}</div>
        </div>

        {{-- Meta --}}
        <div class="pos-m-receipt-meta">
            <div class="pos-m-receipt-meta-row">
                <span>Date</span>
                <span>{{ $returnable->created_at->format('d M Y, H:i') }}</span>
            </div>
            @if($returnable->customer)
            <div class="pos-m-receipt-meta-row">
                <span>Customer</span>
                <span>{{ $returnable->customer->name }}</span>
            </div>
            @endif
            @if($returnable->branch)
            <div class="pos-m-receipt-meta-row">
                <span>Branch</span>
                <span>{{ $returnable->branch->name }}</span>
            </div>
            @endif
            <div class="pos-m-receipt-meta-row">
                <span>Cashier</span>
                <span>{{ $returnable->createdBy?->name ?? '—' }}</span>
            </div>
            <div class="pos-m-receipt-meta-row">
                <span>Status</span>
                <span class="pos-m-badge pos-m-badge--{{ $returnable->status_color }}">{{ $returnable->status_label }}</span>
            </div>
        </div>

        {{-- Container Table --}}
        <div class="pos-m-receipt-table-wrap">
            <table class="pos-m-receipt-table">
                <thead>
                    <tr>
                        <th>Container</th>
                        <th style="text-align:right">Qty</th>
                        <th style="text-align:right">Value each</th>
                        <th style="text-align:right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="font-weight:700">{{ $returnable->product?->name ?? '—' }}</div>
                            <div style="font-size:.625rem;color:#9AAEAE">{{ $returnable->product?->sku }}</div>
                        </td>
                        <td style="text-align:right;font-weight:700">{{ $returnable->bottle_count }}</td>
                        <td style="text-align:right">K {{ number_format($returnable->value_each, 2) }}</td>
                        <td style="text-align:right;font-weight:800;color:#0E6E67">K {{ number_format($returnable->credit_amount, 2) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="font-weight:700">TOTAL CREDIT</td>
                        <td style="text-align:right;font-weight:800;font-size:1rem;color:#0E6E67">K {{ number_format($returnable->credit_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Expiry --}}
        @if($returnable->expiry_date)
        <div class="pos-m-receipt-expiry">
            Valid until {{ $returnable->expiry_date->format('d M Y') }}
        </div>
        @endif

        {{-- Credit summary --}}
        <div class="pos-m-receipt-summary">
            <div class="pos-m-receipt-summary-row">
                <span>Containers returned</span>
                <span style="font-weight:700">{{ $returnable->bottle_count }}</span>
            </div>
            <div class="pos-m-receipt-summary-row">
                <span>Total credit value</span>
                <span style="font-weight:800;color:#0E6E67">K {{ number_format($returnable->credit_amount, 2) }}</span>
            </div>
        </div>

        {{-- Notes --}}
        @if($returnable->notes)
        <div class="pos-m-receipt-notes">
            {{ $returnable->notes }}
        </div>
        @endif

        {{-- Footer --}}
        <div class="pos-m-receipt-footer">
            <p>Present this receipt to redeem your bottle credit at any checkout.</p>
            <p>Credits are non-transferable. Terms apply.</p>
        </div>
    </div>

    {{-- §14.2 — Actions --}}
    <div class="pos-m-receipt-actions">
        <a href="{{ route('pos.m.ret-intake') }}" class="pos-m-btn pos-m-btn--outline">New Intake</a>
        <a href="{{ route('pos.m.ret-register') }}" class="pos-m-btn pos-m-btn--solid">View Register</a>
    </div>

    @include('pos.mobile._bottom-nav', ['active' => 'home'])
</div>
@endsection
