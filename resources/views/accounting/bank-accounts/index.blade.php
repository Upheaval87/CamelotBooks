<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $cashCount = $bankAccounts->filter(fn ($a) => (bool) $a->is_petty_cash)->count();
        $reconDates = \App\Models\Reconciliation::whereIn('bank_account_id', $bankAccounts->pluck('id'))
            ->where('status', 'reconciled')
            ->get(['bank_account_id', 'completed_at'])
            ->groupBy('bank_account_id')
            ->map(fn ($rows) => $rows->max('completed_at'))
            ->map(fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v) : null);
        $lastReconciled = $reconDates->filter()->max();
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="page-head">
                <div>
                    <h1>{{ __('Bank & Cash Accounts') }}</h1>
                    <div class="sub">Manage your bank and cash accounts, registers and reconciliations.</div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.accounts.create') }}" class="btn cta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __('New Account') }}
                    </a>
                </div>
            </div>

            <div class="sgrid">
                <div class="sbox">
                    <div class="l">{{ __('Bank Accounts') }}</div>
                    <div class="v">{{ $bankAccounts->count() }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Cash Accounts') }}</div>
                    <div class="v mint">{{ $cashCount }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Last Reconciled') }}</div>
                    <div class="v" style="font-size:.875rem">{{ $lastReconciled?->format('M d, Y') ?? 'Never' }}</div>
                </div>
            </div>

            <div class="toolbar" style="margin-top:16px">
                <div class="tbtns" style="margin:0">
                    <a href="{{ route('accounting.accounts.create') }}" class="btn ghost sm">{{ __('New Cash Account') }}</a>
                    <a href="{{ route('accounting.bank-accounts.transfer-form') }}" class="btn ghost sm">{{ __('Transfer') }}</a>
                    <span class="chip-t">{{ $bankAccounts->count() }} {{ __('accounts') }}</span>
                </div>
            </div>

            <section class="card card-sec" style="margin-top:16px">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg></span>
                    <h2>{{ __('Accounts') }}</h2>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('Account Name') }}</th>
                                <th>{{ __('Account Number') }}</th>
                                <th>{{ __('Bank') }}</th>
                                <th class="num">{{ __('Book Balance') }} ({{ $cs }})</th>
                                <th>{{ __('Last Reconciled') }}</th>
                                <th class="num">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bankAccounts as $bankAccount)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.bank-accounts.register', $bankAccount) }}" class="row-link" style="font-weight:600">{{ $bankAccount->name }}</a>
                                    </td>
                                    <td class="mono em">{{ $bankAccount->bank_account_number ?? '—' }}</td>
                                    <td class="em">{{ $bankAccount->bank_name ?? '—' }}</td>
                                    <td class="numr">{{ format_number($bankAccount->current_balance) }}</td>
                                    <td class="em">{{ $reconDates->get($bankAccount->id)?->format('M d, Y') ?? 'Never' }}</td>
                                    <td class="num" style="white-space:nowrap">
                                        <a href="{{ route('accounting.bank-accounts.register', $bankAccount) }}" class="btn ghost sm">{{ __('Register') }}</a>
                                        <a href="{{ route('accounting.bank-reconciliation.index', $bankAccount->id) }}" class="btn ghost sm">{{ __('Reconcile') }}</a>
                                        <a href="{{ route('accounting.bank-accounts.manual-form', $bankAccount->id) }}" class="btn ghost sm">{{ __('Manual') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"><div class="empty">No bank accounts found.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
