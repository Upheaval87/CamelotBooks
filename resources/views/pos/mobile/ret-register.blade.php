@extends('layouts.pos-mobile', ['title' => 'BRR Register'])

@section('content')
<div class="pos-m-page" style="padding-bottom:5rem;">

    {{-- §14.3 — Header --}}
    <div class="pos-m-greeting">
        <div class="pos-m-greeting-name">BRR Register</div>
        <div class="pos-m-greeting-sub">
            {{ $stats['all'] }} receipts
            · {{ $stats['pending'] }} pending
            · K {{ number_format($stats['pending'] * 5.00, 2) }} credit
        </div>
    </div>

    {{-- §14.3 — Search --}}
    <div class="pos-m-search-bar">
        <form method="GET" action="{{ route('pos.m.ret-register') }}" class="pos-m-filter-form" style="width:100%">
            <div class="pos-m-search-field">
                <svg class="pos-m-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" name="q" class="pos-m-search-input" value="{{ $q }}" placeholder="Search BRR number or customer…">
                @if($q)
                    <a href="{{ route('pos.m.ret-register', ['status' => $status]) }}" class="pos-m-search-clear">&times;</a>
                @endif
            </div>
            @if($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
        </form>
    </div>

    {{-- §14.3 — Status Chips --}}
    <div class="pos-m-chip-scroll">
        @php
            $chips = [
                '' => 'All',
                'pending' => 'Pending',
                'partially_redeemed' => 'Partial',
                'redeemed' => 'Redeemed',
                'voided' => 'Voided',
            ];
        @endphp
        @foreach($chips as $key => $label)
            <a href="{{ route('pos.m.ret-register', array_filter(['status' => $key ?: null, 'q' => $q])) }}"
               class="pos-m-chip {{ $status === $key || (!$status && $key === '') ? 'pos-m-chip--active' : '' }}">
                {{ $label }}
                @if(isset($stats[$key]))
                    <span class="pos-m-chip-count">{{ $stats[$key] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- §14.3 — BRR Cards --}}
    @forelse($returnables as $r)
        <div class="pos-m-ret-card">
            <div class="pos-m-ret-card-top">
                <div>
                    <div class="pos-m-ret-num">BRR-{{ $r->brr_number }}</div>
                    <div class="pos-m-ret-product">{{ $r->product?->name ?? '—' }}</div>
                </div>
                <span class="pos-m-badge pos-m-badge--{{ $r->status_color }}">{{ $r->status_label }}</span>
            </div>

            <div class="pos-m-ret-detail-grid">
                <div class="pos-m-ret-detail">
                    <span class="pos-m-ret-detail-lbl">Containers</span>
                    <span class="pos-m-ret-detail-val">{{ $r->bottle_count }}</span>
                </div>
                <div class="pos-m-ret-detail">
                    <span class="pos-m-ret-detail-lbl">Credit</span>
                    <span class="pos-m-ret-detail-val" style="color:#0E6E67">K {{ number_format($r->credit_amount, 2) }}</span>
                </div>
                <div class="pos-m-ret-detail">
                    <span class="pos-m-ret-detail-lbl">Issued</span>
                    <span class="pos-m-ret-detail-val">{{ $r->created_at->format('d M') }}</span>
                </div>
                @if($r->customer)
                <div class="pos-m-ret-detail">
                    <span class="pos-m-ret-detail-lbl">Customer</span>
                    <span class="pos-m-ret-detail-val">{{ $r->customer->name }}</span>
                </div>
                @endif
            </div>

            @if($r->expiry_date)
            <div class="pos-m-ret-expiry">
                {{ $r->expiry_date->isPast() ? 'Expired' : 'Expires' }} {{ $r->expiry_date->format('d M Y') }}
            </div>
            @endif

            <div class="pos-m-ret-actions">
                @if($r->isVoidable())
                    <form method="POST" action="{{ route('pos.returnables.void', $r->id) }}" style="display:inline" onsubmit="return confirm('Void BRR-{{ $r->brr_number }}? This cannot be undone.')">
                        @csrf
                        <button type="submit" class="pos-m-ret-act pos-m-ret-act--void">Void</button>
                    </form>
                @endif
                <a href="{{ route('pos.m.ret-receipt', $r->id) }}" class="pos-m-ret-act pos-m-ret-act--view">View</a>
            </div>
        </div>
    @empty
        <div class="pos-m-empty">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            <p>No BRR receipts found.</p>
        </div>
    @endforelse

    @if($returnables->hasPages())
        <div class="pos-m-paginate">{{ $returnables->links() }}</div>
    @endif

    @include('pos.mobile._bottom-nav', ['active' => 'home'])
</div>
@endsection
