<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-crumbs">
            <a href="{{ route('accounting.inventory.dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="sep">/</span>
            <span>{{ __('Stock Transfers & Adjustments') }}</span>
        </div>
        <div class="inv-head">
            <div>
                <h1>{{ __('Stock Transfers & Adjustments') }}</h1>
                <div class="inv-sub">{{ __('Move stock between locations or record quantity adjustments.') }}</div>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'transfers'])

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
            <div class="inv-kpi"><div class="inv-kpi-label">{{ __('Total Transfers') }}</div><div class="inv-kpi-val tabular-nums">{{ $transfers->total() }}</div></div>
            <div class="inv-kpi"><div class="inv-kpi-label">{{ __('Pending') }}</div><div class="inv-kpi-val tabular-nums tabular-nums">{{ $statusCounts['pending'] ?? 0 }}</div></div>
            <div class="inv-kpi"><div class="inv-kpi-label">{{ __('In Transit') }}</div><div class="inv-kpi-val tabular-nums">{{ $statusCounts['in_transit'] ?? 0 }}</div></div>
            <div class="inv-kpi"><div class="inv-kpi-label">{{ __('Completed') }}</div><div class="inv-kpi-val tabular-nums">{{ $statusCounts['completed'] ?? 0 }}</div></div>
        </div>

        <div class="inv-card">
            <div class="inv-card-h">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 105.64-12.36L1 10"/></svg>
                </div>
                {{ __('Recent Transfers') }}
            </div>
            <div class="inv-card-body">
                @forelse($transfers as $transfer)
                <div style="padding:12px 20px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:12px;align-items:center;font-size:13px">
                    <div>
                        <span style="font-weight:700;color:var(--ink)">{{ $transfer->transfer_number }}</span>
                        <div style="color:var(--faint);font-size:12px;margin-top:2px">{{ $transfer->created_at->format('d M Y') }}</div>
                    </div>
                    <div>{{ $transfer->status }}</div>
                    <div class="tabular-nums">{{ $transfer->lines_count ?? 0 }} {{ __('items') }}</div>
                    <div style="text-align:right">
                        <span class="inv-pill-neutral">{{ $transfer->status }}</span>
                    </div>
                </div>
                @empty
                <div class="inv-empty">
                    <div class="inv-empty-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 105.64-12.36L1 10"/></svg>
                    </div>
                    <p>{{ __('No recent transfers or adjustments.') }}</p>
                    <div class="inv-empty-sub">{{ __('Create your first stock transfer to move items between branches.') }}</div>
                </div>
                @endforelse
            </div>
            @if($transfers->hasPages())
            <div style="padding:16px 20px">{{ $transfers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
