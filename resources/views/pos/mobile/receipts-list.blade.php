@extends('layouts.pos-mobile', ['title' => 'Receipts'])

@section('content')
<div class="pos-m-page" style="padding-bottom:5.5rem">

    {{-- Header --}}
    <div class="pos-m-greeting">
        <div class="pos-m-greeting-name">Sales Receipts</div>
        <div class="pos-m-greeting-sub">{{ $todayCount }} transactions today · K {{ number_format($todayRevenue, 2) }} revenue</div>
    </div>

    {{-- Filters --}}
    <div class="pos-m-filters">
        <form method="GET" action="{{ route('pos.m.receipts') }}" class="pos-m-filter-form">
            <div class="pos-m-search-field" style="position:relative">
                <svg class="pos-m-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" name="q" class="pos-m-search-input" placeholder="Search receipt number…">
            </div>
            <div class="pos-m-filter-row">
                <select name="branch_id" class="pos-m-select" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $filterBranch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <select name="terminal_id" class="pos-m-select" onchange="this.form.submit()">
                    <option value="">All Tills</option>
                    @foreach($terminals as $t)
                        <option value="{{ $t->id }}" {{ $filterTerminal == $t->id ? 'selected' : '' }}>{{ $t->identifier }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="pos-m-btn pos-m-btn--solid pos-m-btn--block" style="margin-top:.25rem">Apply Filters</button>
        </form>
    </div>

    {{-- Payment method chips --}}
    <div class="pos-m-chip-scroll">
        @php
            $methods = [
                '' => 'All Methods',
                'cash' => 'Cash',
                'card' => 'Card',
                'mobile' => 'Mobile',
                'credit' => 'Credit',
            ];
        @endphp
        @foreach($methods as $key => $label)
            <a href="{{ route('pos.m.receipts', array_filter(['method' => $key ?: null, 'branch_id' => $filterBranch, 'terminal_id' => $filterTerminal])) }}"
               class="pos-m-chip {{ $filterMethod === $key || (!$filterMethod && $key === '') ? 'pos-m-chip--on' : '' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Day-grouped receipt list --}}
    @forelse($grouped as $group)
        <div class="pos-m-day-group">
            <div class="pos-m-day-label">{{ $group['label'] }}</div>
            @foreach($group['items'] as $sale)
                <a href="{{ route('pos.m.receipt', $sale->id) }}" class="pos-m-r" style="text-decoration:none;color:inherit">
                    <div class="pos-m-list-n">
                        {{ $sale->sale_number }}
                        <small>{{ $sale->customer?->name ?? 'Walk-in' }} · {{ $sale->created_at->format('H:i') }}</small>
                    </div>
                    <div style="text-align:right">
                        <div class="pos-m-list-amt">K {{ number_format($sale->total, 2) }}</div>
                        <div style="font-size:.625rem;color:#9AAEAE">
                            {{ ucfirst(str_replace('_', ' ', $sale->payments->first()?->paymentMethod?->type ?? '—')) }}
                            @if($sale->status === 'refunded')
                                <span class="pos-m-tag pos-m-tag--cred">Refunded</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @empty
        <div class="pos-m-empty">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p>No receipts found.</p>
        </div>
    @endforelse

    @include('pos.mobile._bottom-nav', ['active' => 'receipts'])
</div>
@endsection
