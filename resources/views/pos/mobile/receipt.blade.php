@extends('layouts.pos-mobile', ['title' => 'Receipt'])

@section('content')
<div class="pos-m-page" style="padding-bottom:5.5rem">

    {{-- Success header --}}
    <div class="pos-m-tick">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    </div>
    <div class="pos-m-rtitle">Sale Complete</div>
    <div class="pos-m-rsub">{{ $sale->sale_number }}</div>

    {{-- Receipt document --}}
    <div class="pos-m-doc">
        <div class="pos-m-doc-bh">
            <div class="pos-m-doc-co">
                <span class="pos-m-doc-mk">C</span>
                <div>
                    <div class="pos-m-doc-cn">CamelotBooks</div>
                    <div class="pos-m-doc-ct">Point of Sale · {{ $sale->terminal?->identifier ?? 'TILL-01' }}</div>
                </div>
            </div>
            <div class="pos-m-doc-rn">
                <span style="letter-spacing:.15em;text-transform:uppercase">Receipt</span>
                <b>{{ $sale->sale_number }}</b>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="text-align:left">Item</th>
                    <th style="text-align:center">Qty</th>
                    <th style="text-align:right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->lines as $line)
                <tr>
                    <td>
                        <span style="font-weight:700">{{ $line->product?->name ?? '—' }}</span><br>
                        <span style="color:#9AAEAE">K {{ number_format($line->unit_price, 2) }} each</span>
                    </td>
                    <td style="text-align:center">{{ $line->quantity }}</td>
                    <td class="num" style="text-align:right">K {{ number_format($line->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="words">Amount in words: …only.</div>

        <div class="ft" style="margin-top:.5rem;padding-top:.4375rem;border-top:1px dashed #E4EAE8;font-size:.46875rem;color:#9AAEAE;display:flex;justify-content:space-between">
            <span>www.camelotbooks.com</span>
            <span>Page 1 of 1</span>
        </div>
    </div>

    {{-- Payment summary --}}
    <div class="pos-m-totals" style="margin-top:.75rem">
        <div class="pos-m-totals-row">
            <span>Subtotal</span>
            <span>K {{ number_format($sale->subtotal, 2) }}</span>
        </div>
        @if($sale->discount_total > 0)
        <div class="pos-m-totals-row pos-m-totals-neg">
            <span>Discount</span>
            <span>−K {{ number_format($sale->discount_total, 2) }}</span>
        </div>
        @endif
        @if($sale->tax_total > 0)
        <div class="pos-m-totals-row">
            <span>Tax</span>
            <span>K {{ number_format($sale->tax_total, 2) }}</span>
        </div>
        @endif
        <div class="pos-m-totals-row pos-m-totals-row--grand">
            <span>TOTAL</span>
            <span>K {{ number_format($sale->total, 2) }}</span>
        </div>
        @foreach($sale->payments as $payment)
        <div class="pos-m-totals-row">
            <span>{{ $payment->paymentMethod?->name ?? '—' }}</span>
            <span>K {{ number_format($payment->amount, 2) }}</span>
        </div>
        @if($payment->cash_tendered > 0)
        <div class="pos-m-totals-row">
            <span style="padding-left:1rem">Cash tendered</span>
            <span>K {{ number_format($payment->cash_tendered, 2) }}</span>
        </div>
        @endif
        @if($payment->change_given > 0)
        <div class="pos-m-totals-row">
            <span style="padding-left:1rem">Change</span>
            <span>K {{ number_format($payment->change_given, 2) }}</span>
        </div>
        @endif
        @endforeach
    </div>

    {{-- Actions --}}
    <div class="pos-m-racts">
        <button type="button" class="pos-m-cta" onclick="window.print()">Print</button>
        <a href="{{ route('pos.m.sell') }}" class="pos-m-sq" style="flex:1;height:46px;border-radius:13px;border:1px solid #E4EAE8;background:#fff;font-weight:600;font-size:.8125rem;color:#5F7476;font-family:inherit;cursor:pointer;text-decoration:none;display:grid;place-items:center">New Sale</a>
    </div>

    @include('pos.mobile._bottom-nav', ['active' => 'sell'])
</div>
@endsection
