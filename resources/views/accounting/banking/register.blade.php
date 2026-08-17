<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $lastRow = $transactions->last();
        $closingBalance = $openingBalance + ($lastRow ? (float) $lastRow->running_balance : 0);
        $outstanding = (float) $bankAccount->current_balance - $closingBalance;
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ $bankAccount->name }}</h1>
                    <p class="q2-sub"><span class="q2-mono">{{ $bankAccount->code }}</span> · {{ __('Account register') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.new-transaction', $bankAccount->id) }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('New Transaction') }}</a>
                    <a href="{{ route('accounting.banking.accounts') }}" class="q2-btn q2-btn--sec q2-btn--sm">{{ __('Back') }}</a>
                </div>
            </div>

            <div class="q2-statgrid">
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Book Balance') }}</span>
                        <span class="q2-stat-val">{{ format_number($bankAccount->current_balance) }}</span>
                        <span class="q2-stat-var">{{ $cs }}</span>
                    </div>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Reconciled') }}</span>
                        <span class="q2-stat-val">{{ format_number($reconciledBalance) }}</span>
                        <span class="q2-stat-var">{{ $cs }}</span>
                    </div>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--steel"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Unreconciled') }}</span>
                        <span class="q2-stat-val">{{ format_number($bankAccount->current_balance - $reconciledBalance) }}</span>
                        <span class="q2-stat-var">{{ $cs }}</span>
                    </div>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--ink"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3 10h18M8 6v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Outstanding') }}</span>
                        <span class="q2-stat-val">{{ format_number($outstanding) }}</span>
                        <span class="q2-stat-var">{{ $cs }}</span>
                    </div>
                </div>
            </div>

            <div class="q2-card q2-card--list">
                <form method="GET" action="{{ route('accounting.banking.register', $bankAccount->id) }}" class="q2-filters">
                    <div class="q2-field">
                        <label class="q2-label" for="from_date">{{ __('From') }}</label>
                        <input id="from_date" type="date" name="from_date" class="q2-input" value="{{ $fromDate }}" />
                    </div>
                    <div class="q2-field">
                        <label class="q2-label" for="to_date">{{ __('To') }}</label>
                        <input id="to_date" type="date" name="to_date" class="q2-input" value="{{ $toDate }}" />
                    </div>
                    <div class="q2-filters-actions">
                        <button type="submit" class="q2-btn q2-btn--sec q2-btn--sm">{{ __('Filter') }}</button>
                        <a href="{{ route('accounting.banking.register', $bankAccount->id) }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Clear') }}</a>
                    </div>
                </form>

                <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                    <table class="q2-tbl bk-register">
                        <thead>
                            <tr>
                                <th style="width:11%">{{ __('Date') }}</th>
                                <th style="width:13%">{{ __('Reference') }}</th>
                                <th style="width:34%">{{ __('Description') }}</th>
                                <th style="width:13%" class="q2-right">{{ __('Debit') }} ({{ $cs }})</th>
                                <th style="width:13%" class="q2-right">{{ __('Credit') }} ({{ $cs }})</th>
                                <th style="width:16%" class="q2-right">{{ __('Running Balance') }} ({{ $cs }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bk-register-opening">
                                <td class="q2-amt">{{ __('Opening balance') }}</td>
                                <td colspan="3" class="q2-mono">—</td>
                                <td class="q2-right q2-amt">{{ format_number($openingBalance) }}</td>
                            </tr>
                            @forelse($transactions as $tx)
                                <tr>
                                    <td>{{ $tx->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="q2-mono">{{ $tx->reference ?? '—' }}</td>
                                    <td>
                                        {{ $tx->description }}
                                        @if($tx->source_type === 'transfer')
                                            <span class="q2-cchip">{{ __('Transfer') }}</span>
                                        @elseif($tx->source_type === 'cheque')
                                            <span class="q2-cchip bk-chip--cheque">{{ __('Cheque') }}</span>
                                        @elseif($tx->source_type === 'make_deposit')
                                            <span class="q2-cchip bk-chip--deposit">{{ __('Deposit') }}</span>
                                        @elseif($tx->source_type === 'petty_cash')
                                            <span class="q2-cchip bk-chip--petty">{{ __('Petty cash') }}</span>
                                        @elseif($tx->source_type === 'manual')
                                            <span class="q2-cchip">{{ __('Manual') }}</span>
                                        @endif
                                    </td>
                                    <td class="q2-right bk-debit">{{ $tx->amount > 0 ? format_number($tx->amount) : '—' }}</td>
                                    <td class="q2-right bk-credit">{{ $tx->amount < 0 ? format_number($tx->amount) : '—' }}</td>
                                    <td class="q2-right q2-amt">{{ format_number($openingBalance + $tx->running_balance) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"><div class="q2-empty">{{ __('No transactions in this period.') }}</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bk-register-total">
                                <td colspan="5" class="q2-right q2-lbl">{{ __('Closing Balance') }}</td>
                                <td class="q2-right q2-amt">{{ format_number($closingBalance) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
