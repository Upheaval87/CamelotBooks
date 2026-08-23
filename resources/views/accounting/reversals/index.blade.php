<x-app-layout>
<div class="rv-wrap py-6">
    <div class="rv-head">
        <div>
            <h1 class="rv-title">{{ __('Transaction Reversals') }}</h1>
            <p class="rv-sub">{{ __('Track and manage reversal requests across all posted transactions.') }}</p>
        </div>
        <div class="rv-head-actions">
            <a href="{{ route('accounting.reversals.create') }}" class="rv-btn rv-btn--cta">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                {{ __('Request Reversal') }}
            </a>
        </div>
    </div>

    {{-- KPI Strip --}}
    <div class="rv-kpis">
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('Total Requests') }}</span>
            <span class="rv-kpi-value">{{ $stats['total'] }}</span>
        </div>
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('Pending') }}</span>
            <span class="rv-kpi-value rv-kpi-value--amber">{{ $stats['pending'] }}</span>
        </div>
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('Approved') }}</span>
            <span class="rv-kpi-value rv-kpi-value--green">{{ $stats['approved'] }}</span>
        </div>
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('Rejected') }}</span>
            <span class="rv-kpi-value rv-kpi-value--red">{{ $stats['rejected'] }}</span>
        </div>
    </div>

    <div class="rv-shell">
        <div>
            {{-- Filters --}}
            <form method="GET" action="{{ route('accounting.reversals.index') }}" class="rv-filters">
                <div class="rv-field">
                    <label class="rv-label">{{ __('Status') }}</label>
                    <select name="status" class="rv-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach(\App\Models\TransactionReversalRequest::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rv-field">
                    <label class="rv-label">{{ __('Date From') }}</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="rv-input">
                </div>
                <div class="rv-field">
                    <label class="rv-label">{{ __('Date To') }}</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="rv-input">
                </div>
                <div style="display:flex;gap:.5rem;align-items:flex-end;padding-bottom:1px">
                    <button type="submit" class="rv-btn rv-btn--sec rv-btn--sm">{{ __('Filter') }}</button>
                    <a href="{{ route('accounting.reversals.index') }}" class="rv-btn rv-btn--ghost rv-btn--sm">{{ __('Clear') }}</a>
                </div>
            </form>

            {{-- Request List --}}
            <div class="rv-table-wrap" style="margin-top:1rem">
                @if($requests->count() > 0)
                    <table class="rv-table">
                        <thead>
                            <tr>
                                <th>{{ __('Reference #') }}</th>
                                <th>{{ __('Original JE') }}</th>
                                <th>{{ __('Requester') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="rv-right">{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="rv-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $req)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.reversals.show', $req->id) }}" class="rv-table--link">{{ $req->reference_number }}</a>
                                    </td>
                                    <td>
                                        <span class="rv-mono">{{ $req->journalEntry?->journal_number }}</span>
                                    </td>
                                    <td>{{ $req->requester?->name ?? '—' }}</td>
                                    <td>{{ $req->request_date?->format('d M Y') }}</td>
                                    <td class="rv-right rv-numr">{{ number_format($req->journalEntry?->total_debit ?? 0, 2) }}</td>
                                    <td>
                                        <span class="rv-badge rv-badge--{{ $req->statusColor() }}">
                                            <span class="rv-dot"></span>
                                            {{ $req->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="rv-right">
                                        <a href="{{ route('accounting.reversals.show', $req->id) }}" class="rv-btn rv-btn--ghost rv-btn--xs">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="rv-empty">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <span class="rv-empty-title">{{ __('No reversal requests') }}</span>
                        <span class="rv-empty-text">{{ __('Start by searching for a posted transaction to reverse.') }}</span>
                    </div>
                @endif
            </div>
            <div style="margin-top:1rem">{{ $requests->withQueryString()->links() }}</div>
        </div>

        {{-- Rail --}}
        <aside class="rv-rail">
            <div class="rv-rail-sec">
                <div class="rv-rail-head">
                    <span class="rv-rail-ic">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </span>
                    <span class="rv-rail-title">{{ __('Quick Nav') }}</span>
                </div>
                <div class="rv-vlist">
                    <a href="{{ route('accounting.reversals.index') }}" class="rv-vitem is-active">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></span>
                        {{ __('Pending') }}
                    </a>
                    <a href="{{ route('accounting.reversals.auth') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        {{ __('Authorization Dashboard') }}
                    </a>
                    <a href="{{ route('accounting.reversals.create') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
                        {{ __('Request Reversal') }}
                    </a>
                    <a href="{{ route('accounting.reversals.audit') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></span>
                        {{ __('Audit Trail') }}
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>
</x-app-layout>
