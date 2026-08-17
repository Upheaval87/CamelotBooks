<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $float = (float) $fund->petty_cash_float;
        $current = (float) $fund->current_balance;
        $spent = $float - $current;
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ $fund->name }}</h1>
                    <p class="q2-sub"><span class="q2-mono">{{ $fund->code }}</span> · {{ __('Petty cash fund') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.petty') }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Back') }}</a>
                </div>
            </div>

            <div class="q2-statgrid">
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v12m-3-2a3 3 0 003 3h1a2 2 0 002-2v-1a2 2 0 00-2-2H9a2 2 0 01-2-2V9a2 2 0 012-2h1a3 3 0 013 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Float') }}</span>
                        <span class="q2-stat-val">{{ format_number($float) }}</span>
                        <span class="q2-stat-var">{{ $cs }}</span>
                    </div>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Current Balance') }}</span>
                        <span class="q2-stat-val">{{ format_number($current) }}</span>
                        <span class="q2-stat-var">{{ $cs }}</span>
                    </div>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--red"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Spent') }}</span>
                        <span class="q2-stat-val">{{ format_number($spent) }}</span>
                        <span class="q2-stat-var">{{ $cs }}</span>
                    </div>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--ink"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <div class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Replenishment needed') }}</span>
                        <span class="q2-stat-val">{{ format_number(max($spent, 0)) }}</span>
                        <span class="q2-stat-var">{{ $cs }}</span>
                    </div>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">
                    {{-- Establish / expense / replenish --}}
                    <div class="q2-card q2-card--list">
                        <div class="q2-card-head">
                            <h2 class="q2-card-title">{{ __('Fund Actions') }}</h2>
                        </div>
                        <div class="q2-g3" style="padding:1.25rem;align-items:end">
                            <form method="POST" action="{{ route('accounting.banking.petty.establish', $fund) }}" class="q2-field">
                                @csrf
                                <input type="hidden" name="fund_id" value="{{ $fund->id }}" />
                                <span class="q2-label">{{ __('Establish / Top-up from Bank') }}</span>
                                <div class="q2-g2">
                                    <select name="bank_account_id" class="q2-select" required>
                                        <option value="">— {{ __('Bank account') }} —</option>
                                        @foreach($bankAccounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="date" name="date" class="q2-input" value="{{ old('date', now()->format('Y-m-d')) }}" required />
                                </div>
                                <div class="q2-g2">
                                    <input type="number" step="0.01" min="0.01" name="amount" class="q2-input" placeholder="0.00" required />
                                    <button type="submit" class="q2-btn q2-btn--sec">{{ __('Top-up') }}</button>
                                </div>
                                @error('amount')<span class="q2-error">{{ $message }}</span>@enderror
                            </form>

                            <form method="POST" action="{{ route('accounting.banking.petty.expense', $fund) }}" class="q2-field">
                                @csrf
                                <input type="hidden" name="petty_cash_account_id" value="{{ $fund->id }}" />
                                <span class="q2-label">{{ __('Record Expense') }}</span>
                                <div class="q2-g2">
                                    <select name="debit_account_id" class="q2-select" required>
                                        <option value="">— {{ __('Expense account') }} —</option>
                                        @foreach($expenseAccounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} · {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="date" name="date" class="q2-input" value="{{ old('date', now()->format('Y-m-d')) }}" required />
                                </div>
                                <input type="text" name="description" class="q2-input" placeholder="{{ __('Description') }}" maxlength="500" required />
                                <div class="q2-g2">
                                    <input type="number" step="0.01" min="0.01" name="amount" class="q2-input" placeholder="0.00" required />
                                    <button type="submit" class="q2-btn q2-btn--cta">{{ __('Expense') }}</button>
                                </div>
                                @error('description')<span class="q2-error">{{ $message }}</span>@enderror
                            </form>

                            <form method="POST" action="{{ route('accounting.banking.petty.replenish', $fund) }}" class="q2-field">
                                @csrf
                                <input type="hidden" name="petty_cash_account_id" value="{{ $fund->id }}" />
                                <span class="q2-label">{{ __('Replenish from Bank') }}</span>
                                <div class="q2-g2">
                                    <select name="bank_account_id" class="q2-select" required>
                                        <option value="">— {{ __('Bank account') }} —</option>
                                        @foreach($bankAccounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="date" name="date" class="q2-input" value="{{ old('date', now()->format('Y-m-d')) }}" required />
                                </div>
                                <input type="text" name="description" class="q2-input" placeholder="{{ __('Description (optional)') }}" maxlength="500" />
                                <div class="q2-g2">
                                    <input type="number" step="0.01" min="0.01" name="amount" class="q2-input" placeholder="0.00" required />
                                    <button type="submit" class="q2-btn q2-btn--sec">{{ __('Replenish') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Expenses --}}
                    <div class="q2-card q2-card--list">
                        <div class="q2-card-head">
                            <h2 class="q2-card-title">{{ __('Expenses') }}</h2>
                        </div>
                        <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                            <table class="q2-tbl">
                                <thead>
                                    <tr>
                                        <th style="width:12%">{{ __('Date') }}</th>
                                        <th style="width:14%">{{ __('Reference') }}</th>
                                        <th style="width:40%">{{ __('Description') }}</th>
                                        <th style="width:18%">{{ __('Expense Account') }}</th>
                                        <th style="width:16%" class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($expenses as $line)
                                        <tr>
                                            <td>{{ $line->journalEntry?->date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="q2-mono">{{ $line->journalEntry?->reference ?? '—' }}</td>
                                            <td>{{ $line->journalEntry?->memo ?? $line->memo ?? '—' }}</td>
                                            <td>{{ $line->journalEntry?->lines?->firstWhere('debit', '>', 0)?->account?->name ?? '—' }}</td>
                                            <td class="q2-right q2-amt">{{ format_number($line->credit) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5"><div class="q2-empty">{{ __('No expenses recorded yet.') }}</div></td>
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
                            <a href="{{ route('accounting.banking.petty') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v12m-3-2a3 3 0 003 3h1a2 2 0 002-2v-1a2 2 0 00-2-2H9a2 2 0 01-2-2V9a2 2 0 012-2h1a3 3 0 013 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Petty Cash') }}</a>
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
