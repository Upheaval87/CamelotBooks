<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §1 head --}}
            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('Banking Centre') }}</h1>
                    <p class="q2-sub">{{ __('Bank accounts, transfers, cheques, petty cash and deposits.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.deposits.create') }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Make Deposit') }}</a>
                    <a href="{{ route('accounting.banking.transfers.create') }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Transfer') }}</a>
                    <a href="{{ route('accounting.banking.cheques.create') }}" class="q2-btn q2-btn--cta q2-btn--sm">＋ {{ __('Write Cheque') }}</a>
                </div>
            </div>

            {{-- §2 KPI boxes --}}
            <div class="q2-fbox-grid q2-fbox-grid--4">
                <div class="q2-fbox">
                    <span class="q2-fbox-top">
                        <span class="q2-fbox-ic q2-fbox-ic--teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6zm14 4h-4m4 4h-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="q2-fbox-lbl">{{ __('Bank Balance') }}</span>
                    </span>
                    <span class="q2-fbox-val">{{ $cs }}{{ format_number($bankBalance) }}</span>
                </div>
                <div class="q2-fbox">
                    <span class="q2-fbox-top">
                        <span class="q2-fbox-ic q2-fbox-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v12m-3-2a3 3 0 003 3h1a2 2 0 002-2v-1a2 2 0 00-2-2H9a2 2 0 01-2-2V9a2 2 0 012-2h1a3 3 0 013 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="q2-fbox-lbl">{{ __('Petty Cash') }}</span>
                    </span>
                    <span class="q2-fbox-val">{{ $cs }}{{ format_number($pettyBalance) }}</span>
                </div>
                <div class="q2-fbox">
                    <span class="q2-fbox-top">
                        <span class="q2-fbox-ic q2-fbox-ic--red"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 17V7m8 10V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="q2-fbox-lbl">{{ __('Outstanding Cheques') }}</span>
                    </span>
                    <span class="q2-fbox-val">{{ $cs }}{{ format_number($outstandingTotal) }}</span>
                </div>
                <div class="q2-fbox">
                    <span class="q2-fbox-top">
                        <span class="q2-fbox-ic q2-fbox-ic--steel"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7zm4 3h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="q2-fbox-lbl">{{ __('Undeposited Funds') }}</span>
                    </span>
                    <span class="q2-fbox-val">{{ $cs }}{{ format_number($undepositedBalance) }}</span>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">
                    {{-- Bank accounts --}}
                    <div class="q2-card q2-card--list">
                        <div class="q2-card-head">
                            <h2 class="q2-card-title">{{ __('Bank Accounts') }}</h2>
                            <a href="{{ route('accounting.banking.accounts') }}" class="q2-link">{{ __('View all') }} →</a>
                        </div>
                        @forelse($bankAccounts as $account)
                            <div class="bk-acc-row">
                                <span class="bk-acc-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3 10h18M8 6v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <span class="bk-acc-name">
                                    <a href="{{ route('accounting.banking.register', $account->id) }}" class="q2-link">{{ $account->name }}</a>
                                    <span class="bk-acc-code">{{ $account->code }}</span>
                                </span>
                                <span class="bk-acc-balance">{{ $cs }}{{ format_number($account->current_balance) }}</span>
                            </div>
                        @empty
                            <div class="q2-empty">{{ __('No bank accounts yet.') }}
                                <a href="{{ route('accounting.banking.accounts.create') }}" class="q2-link"> {{ __('Add a bank account') }}</a>.
                            </div>
                        @endforelse
                    </div>

                    {{-- Petty cash funds --}}
                    <div class="q2-card q2-card--list">
                        <div class="q2-card-head">
                            <h2 class="q2-card-title">{{ __('Petty Cash Funds') }}</h2>
                            <a href="{{ route('accounting.banking.petty') }}" class="q2-link">{{ __('View all') }} →</a>
                        </div>
                        @forelse($pettyFunds as $fund)
                            <div class="bk-acc-row">
                                <span class="bk-acc-ic bk-acc-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v12m-3-2a3 3 0 003 3h1a2 2 0 002-2v-1a2 2 0 00-2-2H9a2 2 0 01-2-2V9a2 2 0 012-2h1a3 3 0 013 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span class="bk-acc-name">
                                    <a href="{{ route('accounting.banking.petty.show', $fund->id) }}" class="q2-link">{{ $fund->name }}</a>
                                    <span class="bk-acc-code">{{ $fund->code }}</span>
                                </span>
                                <span class="bk-acc-balance">{{ $cs }}{{ format_number($fund->current_balance) }}</span>
                            </div>
                        @empty
                            <div class="q2-empty">{{ __('No petty cash funds yet.') }}
                                <a href="{{ route('accounting.banking.petty.create') }}" class="q2-link"> {{ __('Create a fund') }}</a>.
                            </div>
                        @endforelse
                    </div>

                    {{-- Recent transactions --}}
                    <div class="q2-card q2-card--list">
                        <div class="q2-card-head">
                            <h2 class="q2-card-title">{{ __('Recent Bank Transactions') }}</h2>
                            <a href="{{ route('accounting.banking.accounts') }}" class="q2-link">{{ __('Open register') }} →</a>
                        </div>
                        @forelse($recentTransactions as $tx)
                            <div class="bk-acc-row">
                                <span class="bk-acc-ic {{ $tx->amount < 0 ? 'bk-acc-ic--red' : 'bk-acc-ic--teal' }}">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="{{ $tx->amount < 0 ? 'M5 12h14M12 5l7 7-7 7' : 'M19 12H5M12 19l-7-7 7-7' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span class="bk-acc-name">
                                    <span class="bk-tx-date">{{ $tx->date?->format('M d, Y') }}</span>
                                    <span class="bk-acc-code">{{ $tx->description }}</span>
                                </span>
                                <span class="bk-acc-balance {{ $tx->amount < 0 ? 'bk-neg' : '' }}">{{ $cs }}{{ format_number($tx->amount) }}</span>
                            </div>
                        @empty
                            <div class="q2-empty">{{ __('No bank transactions yet.') }}</div>
                        @endforelse
                    </div>
                </div>

                {{-- rail --}}
                <div class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Banking') }}</div>
                            <a href="{{ route('accounting.banking.accounts') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('Bank Accounts') }}</a>
                            <a href="{{ route('accounting.banking.transfers') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Transfers') }}</a>
                            <a href="{{ route('accounting.banking.deposits') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5m0 0l-6 6m6-6l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Deposits') }}</a>
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
