<x-app-layout>
<div class="rv-wrap py-6">
    <div class="rv-head">
        <div>
            <h1 class="rv-title">{{ __('Reversal Audit Trail') }}</h1>
            <p class="rv-sub">{{ __('Complete history of all reversal actions across the system.') }}</p>
        </div>
        <div class="rv-head-actions">
            <a href="{{ route('accounting.reversals.rules', ['tab' => 'audit']) }}" class="rv-btn rv-btn--ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                {{ __('Authorization Rules') }}
            </a>
        </div>
    </div>

    <div class="rv-shell">
        <div>
            {{-- Filters --}}
            <form method="GET" action="{{ route('accounting.reversals.audit') }}" class="rv-filters">
                <div class="rv-field">
                    <label class="rv-label">{{ __('Action') }}</label>
                    <select name="action" class="rv-select">
                        <option value="">{{ __('All Actions') }}</option>
                        @foreach(['requested', 'approved', 'rejected', 'clarification_requested', 'clarification_submitted', 'posted_to_gl', 'cancelled', 'timeout'] as $action)
                            <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
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
                    <a href="{{ route('accounting.reversals.audit') }}" class="rv-btn rv-btn--ghost rv-btn--sm">{{ __('Clear') }}</a>
                </div>
            </form>

            {{-- Audit Timeline --}}
            <div class="rv-card" style="margin-top:1rem">
                <div class="rv-card-head">
                    <span class="rv-card-title">{{ __('Audit History') }}</span>
                </div>
                @if($audit->count() > 0)
                    <div class="rv-timeline">
                        @foreach($audit as $log)
                            <div class="rv-timeline-item">
                                <span class="rv-timeline-dot {{ in_array($log->action, ['rejected']) ? 'rv-timeline-dot--red' : (in_array($log->action, ['approved', 'posted_to_gl']) ? 'rv-timeline-dot--green' : 'rv-timeline-dot--amber') }}"></span>
                                <div class="rv-timeline-body">
                                    <span class="rv-timeline-title">{{ \App\Models\ReversalApprovalHistory::actionLabel($log->action) }}</span>
                                    <span class="rv-timeline-sub">— {{ $log->request?->reference_number }} · {{ $log->request?->journalEntry?->journal_number }} · {{ $log->performer?->name }}</span>
                                    @if($log->remarks)
                                        <div class="rv-timeline-sub" style="margin-top:.25rem">{{ $log->remarks }}</div>
                                    @endif
                                    <span class="rv-timeline-time">{{ $log->date_time?->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rv-empty">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        <span class="rv-empty-title">{{ __('No audit records') }}</span>
                        <span class="rv-empty-text">{{ __('No reversal activity recorded yet.') }}</span>
                    </div>
                @endif
            </div>
            <div style="margin-top:1rem">{{ $audit->withQueryString()->links() }}</div>
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
                    <a href="{{ route('accounting.reversals.audit') }}" class="rv-vitem is-active">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></span>
                        {{ __('Audit Trail') }}
                    </a>
                    <a href="{{ route('accounting.reversals.rules') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06"/></svg></span>
                        {{ __('Rules') }}
                    </a>
                    <a href="{{ route('accounting.reversals.auth') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        {{ __('Authorization Dashboard') }}
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
