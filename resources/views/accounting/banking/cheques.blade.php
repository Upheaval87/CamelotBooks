<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('Cheques') }}</h1>
                    <p class="q2-sub">{{ __('Cheque register across your bank accounts.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.cheques.create') }}" class="q2-btn q2-btn--cta q2-btn--sm">＋ {{ __('Write Cheque') }}</a>
                </div>
            </div>

            <form method="GET" action="{{ route('accounting.banking.cheques') }}" class="q2-filters">
                <div class="q2-field">
                    <label class="q2-label" for="bank_account_id">{{ __('Bank Account') }}</label>
                    <select id="bank_account_id" name="bank_account_id" class="q2-select">
                        <option value="">— {{ __('All accounts') }} —</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}" @selected((string) $bankAccountId === (string) $acc->id)>{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
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
                    <a href="{{ route('accounting.banking.cheques') }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Clear') }}</a>
                </div>
            </form>

            <div class="q2-shell">
                <div class="q2-main">
                    <div class="q2-card q2-card--list">
                        <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                            <table class="q2-tbl">
                                <thead>
                                    <tr>
                                        <th style="width:12%">{{ __('Cheque №') }}</th>
                                        <th style="width:12%">{{ __('Date') }}</th>
                                        <th style="width:20%">{{ __('Payee') }}</th>
                                        <th style="width:18%">{{ __('Bank Account') }}</th>
                                        <th style="width:14%" class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                        <th style="width:12%">{{ __('Status') }}</th>
                                        <th style="width:12%" class="q2-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cheques as $cheque)
                                        <tr>
                                            <td class="q2-mono">
                                                <a href="{{ route('accounting.banking.cheques.show', $cheque->id) }}" class="q2-link">{{ str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) }}</a>
                                            </td>
                                            <td>{{ $cheque->date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="q2-amt" style="font-weight:600;color:var(--deep-3,#0A2E32)">{{ $cheque->payee }}</td>
                                            <td>{{ $cheque->bankAccount?->name ?? '—' }}</td>
                                            <td class="q2-right q2-amt">{{ format_number($cheque->amount) }}</td>
                                            <td>
                                                @switch($cheque->status)
                                                    @case(\App\Models\Cheque::STATUS_OUTSTANDING)
                                                        <span class="q2-badge q2-badge--sent"><span class="q2-dot"></span>{{ __('Outstanding') }}</span>
                                                        @break
                                                    @case(\App\Models\Cheque::STATUS_CLEARED)
                                                        <span class="q2-badge q2-badge--accepted"><span class="q2-dot"></span>{{ __('Cleared') }}</span>
                                                        @break
                                                    @case(\App\Models\Cheque::STATUS_VOID)
                                                        <span class="q2-badge q2-badge--declined"><span class="q2-dot"></span>{{ __('Void') }}</span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td class="q2-right">
                                                <div class="q2-ibtn-row">
                                                    <a href="{{ route('accounting.banking.cheques.show', $cheque->id) }}" class="q2-ibtn" title="{{ __('View') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3 10h18M8 6v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7"><div class="q2-empty">{{ __('No cheques match the current filters.') }}</div></td>
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
                            <a href="{{ route('accounting.banking.cheques') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 17V7m8 10V7M6 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Cheques') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
