<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('Bank Accounts') }}</h1>
                    <p class="q2-sub">{{ __('Manage the bank accounts your company uses.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.accounts.create') }}" class="q2-btn q2-btn--cta q2-btn--sm">＋ {{ __('Add Bank Account') }}</a>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">
                    <div class="q2-card q2-card--list">
                        <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                            <table class="q2-tbl">
                                <thead>
                                    <tr>
                                        <th style="width:14%">{{ __('Code') }}</th>
                                        <th style="width:24%">{{ __('Account Name') }}</th>
                                        <th style="width:12%">{{ __('Currency') }}</th>
                                        <th style="width:12%">{{ __('Next Cheque №') }}</th>
                                        <th style="width:13%" class="q2-right">{{ __('Balance') }} ({{ $cs }})</th>
                                        <th style="width:10%">{{ __('Status') }}</th>
                                        <th style="width:15%" class="q2-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($accounts as $account)
                                        <tr>
                                            <td class="q2-mono">{{ $account->code }}</td>
                                            <td class="q2-amt" style="font-weight:600;color:var(--deep-3,#0A2E32)">
                                                <a href="{{ route('accounting.banking.register', $account->id) }}" class="q2-link">{{ $account->name }}</a>
                                            </td>
                                            <td>{{ $account->currency ?? '—' }}</td>
                                            <td class="q2-mono">{{ str_pad($account->next_cheque_number ?? 1, 6, '0', STR_PAD_LEFT) }}</td>
                                            <td class="q2-right q2-amt">{{ format_number($account->current_balance) }}</td>
                                            <td>
                                                @if($account->is_active)
                                                    <span class="q2-badge q2-badge--accepted"><span class="q2-dot"></span>{{ __('Active') }}</span>
                                                @else
                                                    <span class="q2-badge q2-badge--declined"><span class="q2-dot"></span>{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td class="q2-right">
                                                <div class="q2-ibtn-row">
                                                    <a href="{{ route('accounting.banking.register', $account->id) }}" class="q2-ibtn" title="{{ __('Register') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                    </a>
                                                    <a href="{{ route('accounting.banking.accounts.edit', $account->id) }}" class="q2-ibtn" title="{{ __('Edit') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>
                                                    <form method="POST" action="{{ route('accounting.banking.accounts.toggle', $account->id) }}" class="inline" onsubmit="fbConfirmSubmit(event, '{{ $account->is_active ? 'Deactivate' : 'Activate' }} this bank account?')">
                                                        @csrf
                                                        <button type="submit" class="q2-ibtn q2-ibtn--del" title="{{ $account->is_active ? __('Deactivate') : __('Activate') }}">
                                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7"><div class="q2-empty">{{ __('No bank accounts yet.') }}</div></td>
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
