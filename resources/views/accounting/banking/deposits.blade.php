<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('Deposits') }}</h1>
                    <p class="q2-sub">{{ __('Record deposits of undeposited receipts into bank accounts.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.deposits.create') }}" class="q2-btn q2-btn--cta q2-btn--sm">＋ {{ __('New Deposit') }}</a>
                </div>
            </div>

            <div class="q2-fbox-grid q2-fbox-grid--4">
                <div class="q2-fbox">
                    <span class="q2-fbox-top">
                        <span class="q2-fbox-ic q2-fbox-ic--teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 4l9 6.5M5 9v11h14V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="q2-fbox-lbl">{{ __('Undeposited Funds') }}</span>
                    </span>
                    <span class="q2-fbox-val">{{ $cs }}{{ format_number($undepositedBalance) }}</span>
                </div>
                <div class="q2-fbox">
                    <span class="q2-fbox-top">
                        <span class="q2-fbox-ic q2-fbox-ic--steel"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <span class="q2-fbox-lbl">{{ __('Bank Accounts') }}</span>
                    </span>
                    <span class="q2-fbox-val">{{ $bankAccounts->count() }}</span>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">
                    <div class="q2-card q2-card--list">
                        <div class="q2-card-head">
                            <h2 class="q2-card-title">{{ __('Undeposited Receipts') }}</h2>
                        </div>
                        <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                            <table class="q2-tbl">
                                <thead>
                                    <tr>
                                        <th style="width:14%">{{ __('Date') }}</th>
                                        <th style="width:22%">{{ __('Reference') }}</th>
                                        <th style="width:42%">{{ __('Description') }}</th>
                                        <th style="width:22%" class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($undepositedLines as $line)
                                        <tr>
                                            <td>{{ $line->journalEntry?->date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="q2-mono">{{ $line->journalEntry?->reference ?? '—' }}</td>
                                            <td>{{ $line->journalEntry?->memo ?? $line->memo ?? '—' }}</td>
                                            <td class="q2-right q2-amt">{{ format_number($line->debit) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4"><div class="q2-empty">{{ __('No undeposited receipts.') }}</div></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Banking') }}</div>
                            <a href="{{ route('accounting.banking.dashboard') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 4l9 6.5M5 9v11h14V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Banking Centre') }}</a>
                            <a href="{{ route('accounting.banking.accounts') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('Bank Accounts') }}</a>
                            <a href="{{ route('accounting.banking.deposits') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5m0 0l-6 6m6-6l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Deposits') }}</a>
                            <a href="{{ route('accounting.banking.cheques') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 17V7m8 10V7M6 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Cheques') }}</a>
                            <a href="{{ route('accounting.banking.petty') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v12m-3-2a3 3 0 003 3h1a2 2 0 002-2v-1a2 2 0 00-2-2H9a2 2 0 01-2-2V9a2 2 0 012-2h1a3 3 0 013 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Petty Cash') }}</a>
                        </div>
                        <div class="q2-rule"></div>
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Reports') }}</div>
                            <a href="{{ route('accounting.banking.reports') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 17v-5M13 17V7M17 17v-2M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Reports') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
