<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $statusBadge = match($cheque->status) {
            \App\Models\Cheque::STATUS_OUTSTANDING => ['q2-badge--sent', __('Outstanding')],
            \App\Models\Cheque::STATUS_CLEARED => ['q2-badge--accepted', __('Cleared')],
            default => ['q2-badge--declined', __('Void')],
        };
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('Cheque') }} #{{ str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) }}</h1>
                    <p class="q2-sub">
                        <span class="q2-badge {{ $statusBadge[0] }}"><span class="q2-dot"></span>{{ $statusBadge[1] }}</span>
                        &nbsp;·&nbsp; {{ $cheque->bankAccount?->name ?? '—' }}
                    </p>
                </div>
                <div class="q2-head-actions">
                    @if($cheque->status === \App\Models\Cheque::STATUS_OUTSTANDING)
                        <form method="POST" action="{{ route('accounting.banking.cheques.clear', $cheque->id) }}" class="inline" onsubmit="fbConfirmSubmit(event, 'Mark this cheque as cleared?')">
                            @csrf
                            <button type="submit" class="q2-btn q2-btn--sec q2-btn--sm">{{ __('Mark Cleared') }}</button>
                        </form>
                        <form method="POST" action="{{ route('accounting.banking.cheques.void', $cheque->id) }}" class="inline" onsubmit="fbConfirmSubmit(event, 'Void this cheque? This posts a reversal journal entry.', { type: 'danger' })">
                            @csrf
                            <button type="submit" class="q2-btn q2-btn--danger q2-btn--sm">{{ __('Void Cheque') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('accounting.banking.cheques') }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Back') }}</a>
                </div>
            </div>

            <div class="q2-statgrid">
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 17V7m8 10V7M6 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Cheque №') }}</span>
                        <span class="q2-stat-val q2-mono">{{ str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--steel"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Date') }}</span>
                        <span class="q2-stat-val">{{ $cheque->date?->format('M d, Y') ?? '—' }}</span>
                    </div>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--ink"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 20h5v-2a3 3 0 00-5.36-1.86M9 20H4v-2a3 3 0 015.36-1.86M16 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM6 10a2 2 0 11-4 0 2 2 0 014 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Payee') }}</span>
                        <span class="q2-stat-val">{{ $cheque->payee }}</span>
                    </div>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--red"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v6m0 4h.01M3 12a9 9 0 1118 0 9 9 0 01-18 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Amount') }}</span>
                        <span class="q2-stat-val">{{ format_number($cheque->amount) }}</span>
                        <span class="q2-stat-var">{{ $cs }}</span>
                    </div>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">
                    <div class="q2-card q2-card--list">
                        <div class="q2-card-head">
                            <h2 class="q2-card-title">{{ __('Cheque Details') }}</h2>
                        </div>
                        <div class="q2-g4" style="padding:1.25rem">
                            <div class="q2-field">
                                <span class="q2-label">{{ __('Bank Account') }}</span>
                                <span class="q2-val">{{ $cheque->bankAccount?->name ?? '—' }}</span>
                            </div>
                            <div class="q2-field">
                                <span class="q2-label">{{ __('Memo') }}</span>
                                <span class="q2-val">{{ $cheque->memo ?? '—' }}</span>
                            </div>
                            <div class="q2-field">
                                <span class="q2-label">{{ __('Status') }}</span>
                                <span class="q2-val">
                                    <span class="q2-badge {{ $statusBadge[0] }}"><span class="q2-dot"></span>{{ $statusBadge[1] }}</span>
                                </span>
                            </div>
                            <div class="q2-field">
                                <span class="q2-label">{{ __('Created by') }}</span>
                                <span class="q2-val">{{ $cheque->createdBy?->name ?? '—' }}</span>
                            </div>
                            @if($cheque->status === \App\Models\Cheque::STATUS_VOID)
                                <div class="q2-field">
                                    <span class="q2-label">{{ __('Voided by') }}</span>
                                    <span class="q2-val">{{ $cheque->voidedBy?->name ?? '—' }}</span>
                                </div>
                            @endif
                            @if($cheque->journal_entry_id)
                                <div class="q2-field">
                                    <span class="q2-label">{{ __('Journal Entry') }}</span>
                                    <span class="q2-val">
                                        <a href="{{ route('accounting.journal-entries.show', $cheque->journal_entry_id) }}" class="q2-link">#{{ $cheque->journal_entry_id }} →</a>
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Banking') }}</div>
                            <a href="{{ route('accounting.banking.cheques') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 17V7m8 10V7M6 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Cheques') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
