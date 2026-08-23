<x-app-layout>
<div class="rv-wrap py-6">
    <div class="rv-head">
        <div>
            <h1 class="rv-title">{{ __('Authorization Dashboard') }}</h1>
            <p class="rv-sub">{{ __('Monitor and manage pending reversal authorizations across your team.') }}</p>
        </div>
    </div>

    <div class="rv-kpis">
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('My Queue') }}</span>
            <span class="rv-kpi-value rv-kpi-value--amber">{{ $stats['myQueue'] }}</span>
        </div>
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('Total Pending') }}</span>
            <span class="rv-kpi-value rv-kpi-value--amber">{{ $stats['totalPending'] }}</span>
        </div>
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('Approved') }}</span>
            <span class="rv-kpi-value rv-kpi-value--green">{{ $stats['totalApproved'] }}</span>
        </div>
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('Rejected') }}</span>
            <span class="rv-kpi-value rv-kpi-value--red">{{ $stats['totalRejected'] }}</span>
        </div>
    </div>

    <div class="rv-shell">
        <div>
            <div class="rv-card">
                <div class="rv-card-head">
                    <span class="rv-card-title">{{ __('Quick Actions') }}</span>
                </div>
                <div style="display:flex;gap:.75rem;flex-wrap:wrap">
                    <a href="{{ route('accounting.reversals.auth.queue') }}" class="rv-btn rv-btn--cta">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        {{ __('My Pending Queue') }}
                    </a>
                    <a href="{{ route('accounting.reversals.rules') }}" class="rv-btn rv-btn--ghost">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        {{ __('Authorization Rules') }}
                    </a>
                    <a href="{{ route('accounting.reversals.audit') }}" class="rv-btn rv-btn--ghost">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        {{ __('Reversal History') }}
                    </a>
                </div>
            </div>
        </div>

        <aside class="rv-rail">
            <div class="rv-rail-sec">
                <div class="rv-rail-head">
                    <span class="rv-rail-ic">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </span>
                    <span class="rv-rail-title">{{ __('Quick Nav') }}</span>
                </div>
                <div class="rv-vlist">
                    <a href="{{ route('accounting.reversals.auth') }}" class="rv-vitem is-active">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        {{ __('Authorization Dashboard') }}
                    </a>
                    <a href="{{ route('accounting.reversals.auth.queue') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/></svg></span>
                        {{ __('My Queue') }}
                    </a>
                    <a href="{{ route('accounting.reversals.rules') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06"/></svg></span>
                        {{ __('Rules') }}
                    </a>
                    <a href="{{ route('accounting.reversals.index') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></span>
                        {{ __('Reversal Dashboard') }}
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>
</x-app-layout>
