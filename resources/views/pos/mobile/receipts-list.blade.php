@extends('layouts.pos-mobile', ['title' => 'Receipts'])

@section('content')
<div class="pos-m-page" style="padding-bottom: 5rem;">

    {{-- §10 — Header --}}
    <div class="pos-m-greeting">
        <div class="pos-m-greeting-name">Receipts</div>
        <div class="pos-m-greeting-sub">
            {{ $todayCount }} today · K {{ number_format($todayRevenue, 2) }}
            · {{ auth()->user()->name ?? 'Cashier' }}
        </div>
    </div>

    {{-- §10 — Branch + Till selectors --}}
    <div class="pos-m-filters">
        <form method="GET" action="{{ route('pos.m.receipts') }}" class="pos-m-filter-form">
            <div class="pos-m-filter-row">
                <div class="pos-m-filter-field">
                    <label class="pos-m-label">Branch</label>
                    <select name="branch_id" class="pos-m-select" onchange="this.form.submit()">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $filterBranch == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="pos-m-filter-field">
                    <label class="pos-m-label">Till / Register</label>
                    <select name="terminal_id" class="pos-m-select" onchange="this.form.submit()">
                        <option value="">All tills</option>
                        @foreach($terminals as $t)
                            <option value="{{ $t->id }}" {{ $filterTerminal == $t->id ? 'selected' : '' }}>
                                {{ $t->identifier }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            {{-- §10 — Payment filter chips --}}
            <div class="pos-m-chip-row">
                @foreach(['all' => 'All', 'cash' => 'Cash', 'card' => 'Card', 'mobile' => 'Mobile', 'credit' => 'Credit'] as $key => $label)
                    <button type="submit" name="method" value="{{ $key }}"
                        class="pos-m-chip {{ $filterMethod === $key || (!$filterMethod && $key === 'all') ? 'pos-m-chip--active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <input type="hidden" name="branch_id" value="{{ $filterBranch }}">
            <input type="hidden" name="terminal_id" value="{{ $filterTerminal }}">
        </form>
    </div>

    {{-- §10 — Day-grouped receipt list --}}
    @if($grouped->isEmpty())
        <div class="pos-m-empty">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p>No receipts found.</p>
        </div>
    @else
        @foreach($grouped as $group)
            <div class="pos-m-day-group">
                <div class="pos-m-day-label">{{ $group['label'] }}</div>
                @foreach($group['items'] as $sale)
                    <a href="{{ route('pos.m.receipt', $sale->id) }}" class="pos-m-recent-item">
                        <div class="pos-m-recent-info">
                            <div class="pos-m-recent-num">
                                #{{ $sale->sale_number }}
                                @if($sale->payments->contains('paymentMethod.type', 'credit'))
                                    <span class="pos-m-tag pos-m-tag--credit">On credit</span>
                                @endif
                            </div>
                            <div class="pos-m-recent-meta">
                                {{ $sale->terminal?->identifier ?? '—' }}
                                · {{ $sale->created_at->format('H:i') }}
                                · {{ $sale->customer?->name ?? 'Walk-in' }}
                                · {{ $sale->payments->first()?->paymentMethod?->name ?? '—' }}
                            </div>
                        </div>
                        <div class="pos-m-recent-right">
                            <div class="pos-m-recent-total">K {{ number_format($sale->total, 2) }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endforeach
    @endif

</div>

@include('pos.mobile._bottom-nav', ['active' => 'receipts'])
@endsection
