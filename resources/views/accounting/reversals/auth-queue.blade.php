<x-app-layout>
<div class="rv-wrap py-6">
    <div class="rv-head">
        <div>
            <h1 class="rv-title">{{ __('Pending Authorization Queue') }}</h1>
            <p class="rv-sub">{{ __('Review and act on reversal requests assigned to you.') }}</p>
        </div>
        <div class="rv-head-actions">
            <a href="{{ route('accounting.reversals.auth') }}" class="rv-btn rv-btn--ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                {{ __('Authorization Dashboard') }}
            </a>
        </div>
    </div>

    <div class="rv-shell">
        <div>
            {{-- Filters --}}
            <form method="GET" action="{{ route('accounting.reversals.auth.queue') }}" class="rv-filters">
                <div class="rv-field">
                    <label class="rv-label">{{ __('Status') }}</label>
                    <select name="status" class="rv-select">
                        <option value="">{{ __('All Pending') }}</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>{{ __('Pending Approval') }}</option>
                        <option value="needs_clarification" {{ request('status') === 'needs_clarification' ? 'selected' : '' }}>{{ __('Needs Clarification') }}</option>
                    </select>
                </div>
                <div class="rv-field">
                    <label class="rv-label">{{ __('Min Amount') }}</label>
                    <input type="number" name="min_amount" value="{{ request('min_amount') }}" step="0.01" class="rv-input" placeholder="0.00">
                </div>
                <div class="rv-field">
                    <label class="rv-label">{{ __('Max Amount') }}</label>
                    <input type="number" name="max_amount" value="{{ request('max_amount') }}" step="0.01" class="rv-input" placeholder="999999.99">
                </div>
                <div style="display:flex;gap:.5rem;align-items:flex-end;padding-bottom:1px">
                    <button type="submit" class="rv-btn rv-btn--sec rv-btn--sm">{{ __('Filter') }}</button>
                    <a href="{{ route('accounting.reversals.auth.queue') }}" class="rv-btn rv-btn--ghost rv-btn--sm">{{ __('Clear') }}</a>
                </div>
            </form>

            {{-- Queue Table --}}
            <div class="rv-table-wrap" style="margin-top:1rem">
                @if($queue->count() > 0)
                    <table class="rv-table">
                        <thead>
                            <tr>
                                <th>{{ __('Reference #') }}</th>
                                <th>{{ __('Original JE') }}</th>
                                <th>{{ __('Requester') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="rv-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($queue as $req)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.reversals.auth.show', $req->id) }}" class="rv-table--link">{{ $req->request?->reference_number }}</a>
                                    </td>
                                    <td>
                                        <span class="rv-mono">{{ $req->request?->journalEntry?->journal_number }}</span>
                                    </td>
                                    <td>{{ $req->request?->requester?->name ?? '—' }}</td>
                                    <td class="rv-numr">{{ number_format($req->request?->journalEntry?->total_debit ?? 0, 2) }}</td>
                                    <td>
                                        <span class="rv-badge rv-badge--{{ $req->statusColor() }}">
                                            <span class="rv-dot"></span>
                                            {{ $req->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="rv-right">
                                        <a href="{{ route('accounting.reversals.auth.show', $req->id) }}" class="rv-btn rv-btn--cta rv-btn--xs">{{ __('Review') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="rv-empty">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span class="rv-empty-title">{{ __('Queue is clear') }}</span>
                        <span class="rv-empty-text">{{ __('No reversal requests pending your authorization.') }}</span>
                    </div>
                @endif
            </div>
            <div style="margin-top:1rem">{{ $queue->withQueryString()->links() }}</div>
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
                    <a href="{{ route('accounting.reversals.auth.queue') }}" class="rv-vitem is-active">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/></svg></span>
                        {{ __('My Queue') }}
                    </a>
                    <a href="{{ route('accounting.reversals.auth') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        {{ __('Authorization Dashboard') }}
                    </a>
                    <a href="{{ route('accounting.reversals.rules') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg></span>
                        {{ __('Rules') }}
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
