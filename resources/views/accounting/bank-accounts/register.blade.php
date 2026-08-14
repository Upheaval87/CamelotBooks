<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $totalDebit = $transactions->sum(fn ($t) => (float) $t->amount > 0 ? (float) $t->amount : 0);
        $totalCredit = $transactions->sum(fn ($t) => (float) $t->amount < 0 ? abs((float) $t->amount) : 0);
        $lastReconciledRaw = \App\Models\Reconciliation::where('bank_account_id', $bankAccount->id)
            ->where('company_id', $bankAccount->company_id)
            ->where('status', 'reconciled')
            ->max('completed_at');
        $lastReconciled = $lastReconciledRaw ? \Illuminate\Support\Carbon::parse($lastReconciledRaw) : null;
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="sticky-head">
                <div>
                    <h1>{{ __('Bank Register') }} <span class="mono-chip">{{ $bankAccount->code }}</span></h1>
                    <div class="sub">
                        {{ $bankAccount->name }}
                        @if($bankAccount->bank_name) · {{ $bankAccount->bank_name }} @endif
                        @if($bankAccount->bank_account_number) · {{ $bankAccount->bank_account_number }} @endif
                    </div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.bank-accounts.manual-form', $bankAccount->id) }}" class="btn btn-sec">{{ __('Manual Transaction') }}</a>
                    <a href="{{ route('accounting.bank-reconciliation.index', $bankAccount->id) }}" class="btn btn-sec">{{ __('Reconcile') }}</a>
                    <a href="{{ route('accounting.bank-accounts.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <div class="sgrid">
                <div class="sbox">
                    <div class="l">{{ __('Book Balance') }} ({{ $cs }})</div>
                    <div class="v">{{ format_number($bankAccount->current_balance) }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Reconciled Balance') }} ({{ $cs }})</div>
                    <div class="v mint">{{ format_number($reconciledBalance) }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Transactions') }}</div>
                    <div class="v">{{ $transactions->count() }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Last Reconciled') }}</div>
                    <div class="v" style="font-size:.875rem">{{ $lastReconciled?->format('M d, Y') ?? 'Never' }}</div>
                </div>
            </div>

            <div class="toolbar" style="margin-top:16px">
                <form method="GET" action="{{ route('accounting.bank-accounts.register', $bankAccount) }}" class="controls">
                    <div class="field" style="margin:0">
                        <label>{{ __('From Date') }}</label>
                        <input type="date" name="from_date" class="input" value="{{ $fromDate ?? request('from_date') }}" style="width:auto" />
                    </div>
                    <div class="field" style="margin:0">
                        <label>{{ __('To Date') }}</label>
                        <input type="date" name="to_date" class="input" value="{{ $toDate ?? request('to_date') }}" style="width:auto" />
                    </div>
                    <button type="submit" class="btn ghost sm">{{ __('Filter') }}</button>
                    @if(($fromDate ?? request('from_date')) || ($toDate ?? request('to_date')))
                        <a href="{{ route('accounting.bank-accounts.register', $bankAccount) }}" class="btn ghost sm">{{ __('Clear') }}</a>
                    @endif
                    <span class="chip-t">{{ $transactions->count() }} {{ __('transactions') }}</span>
                </form>
            </div>

            <section class="card card-sec" style="margin-top:16px">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/></svg></span>
                    <h2>{{ __('Register') }}</h2>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:11%">{{ __('Date') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th style="width:14%">{{ __('Reference') }}</th>
                                <th class="num" style="width:11%">{{ __('Debit') }} ({{ $cs }})</th>
                                <th class="num" style="width:11%">{{ __('Credit') }} ({{ $cs }})</th>
                                <th class="num" style="width:12%">{{ __('Balance') }}</th>
                                <th class="num" style="width:10%">{{ __('Cleared') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                                @php $amt = (float) $transaction->amount; @endphp
                                <tr>
                                    <td class="em">{{ $transaction->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="em">{{ $transaction->description }}</td>
                                    <td class="mono em">{{ $transaction->reference ?? '—' }}</td>
                                    <td class="numr">{{ $amt > 0 ? format_number($amt) : '—' }}</td>
                                    <td class="numr">{{ $amt < 0 ? format_number(abs($amt)) : '—' }}</td>
                                    <td class="numr" style="font-weight:600">{{ format_number($transaction->running_balance) }}</td>
                                    <td class="num">
                                        @if($transaction->is_reconciled)
                                            <span class="badge b-post"><span class="bdot"></span>{{ __('Cleared') }}</span>
                                        @else
                                            <span class="badge b-gray"><span class="bdot"></span>{{ __('Pending') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"><div class="empty">No transactions found.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($transactions->count() > 0)
                    <div class="li-totals">
                        <div class="box">
                            <div class="trow"><span>{{ __('Total Debits') }}</span><span class="v">{{ format_number($totalDebit) }}</span></div>
                            <div class="trow"><span>{{ __('Total Credits') }}</span><span class="v">{{ format_number($totalCredit) }}</span></div>
                            <div class="trow total"><span>{{ __('Net') }}</span><span class="v">{{ format_number($totalDebit - $totalCredit) }}</span></div>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
