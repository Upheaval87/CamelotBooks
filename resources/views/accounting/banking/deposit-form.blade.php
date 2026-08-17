<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('New Deposit') }}</h1>
                    <p class="q2-sub">{{ __('Deposit undeposited receipts into a bank account.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.deposits') }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Cancel') }}</a>
                </div>
            </div>

            <div class="q2-shell q2-shell--form">
                <div class="q2-main">
                    <form method="POST" action="{{ route('accounting.banking.deposits.store') }}" id="deposit-form"
                          x-data="{
                            selected: [],
                            get total() {
                                return this.selected.reduce((sum, id) => sum + Number(document.querySelector(`#line-amt-${id}`)?.dataset.amount || 0), 0);
                            }
                          }">
                        @csrf

                        <div class="q2-sec">
                            <div class="q2-sec-head">
                                <span class="q2-sec-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5m0 0l-6 6m6-6l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <div>
                                    <div class="q2-sec-title">{{ __('Deposit Details') }}</div>
                                    <div class="q2-sec-sub">{{ __('Pick the receipts to deposit — the total is carried into the amount.') }}</div>
                                </div>
                            </div>
                            <div class="q2-sec-body">
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="bank_account_id">{{ __('Deposit To') }} <span class="q2-req">*</span></label>
                                        <select id="bank_account_id" name="bank_account_id" class="q2-select" required>
                                            <option value="">— {{ __('Choose bank account') }} —</option>
                                            @foreach($bankAccounts as $acc)
                                                <option value="{{ $acc->id }}" @selected(old('bank_account_id') == $acc->id)>{{ $acc->name }} · {{ $acc->code }}</option>
                                            @endforeach
                                        </select>
                                        @error('bank_account_id')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="date">{{ __('Date') }} <span class="q2-req">*</span></label>
                                        <input id="date" type="date" name="date" class="q2-input" required value="{{ old('date', now()->format('Y-m-d')) }}" />
                                        @error('date')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="amount">{{ __('Deposit Amount') }} <span class="q2-req">*</span></label>
                                        <input id="amount" type="number" step="0.01" min="0.01" name="amount" class="q2-input" required value="{{ old('amount') }}" x-model.number="total" placeholder="0.00" />
                                        @error('amount')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="reference">{{ __('Reference') }}</label>
                                        <input id="reference" type="text" name="reference" class="q2-input" maxlength="255" value="{{ old('reference') }}" placeholder="e.g. DEP-001" />
                                        @error('reference')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-field">
                                    <label class="q2-label" for="description">{{ __('Description') }}</label>
                                    <input id="description" type="text" name="description" class="q2-input" maxlength="500" value="{{ old('description') }}" placeholder="e.g. Daily banking of receipts" />
                                    @error('description')<span class="q2-error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="q2-sec">
                            <div class="q2-sec-head">
                                <span class="q2-sec-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 4l9 6.5M5 9v11h14V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <div>
                                    <div class="q2-sec-title">{{ __('Receipts to Deposit') }}</div>
                                    <div class="q2-sec-sub">{{ __('Select one or more undeposited receipts.') }}</div>
                                </div>
                            </div>
                            <div class="q2-sec-body">
                                @forelse($undepositedLines as $line)
                                    <label class="bk-receipt-row">
                                        <input type="checkbox" name="journal_entry_ids[]" value="{{ $line->journal_entry_id }}"
                                               class="bk-receipt-check" x-model="selected" />
                                        <span class="bk-receipt-meta">
                                            <span class="bk-receipt-ref">{{ $line->journalEntry?->reference ?? __('—') }}</span>
                                            <span class="bk-acc-code">{{ $line->journalEntry?->date?->format('M d, Y') ?? '—' }} · {{ $line->journalEntry?->memo ?? $line->memo ?? '—' }}</span>
                                        </span>
                                        <span class="bk-receipt-amt" id="line-amt-{{ $line->journal_entry_id }}" data-amount="{{ (float) $line->debit }}">{{ $cs }}{{ format_number($line->debit) }}</span>
                                    </label>
                                @empty
                                    <div class="q2-empty">{{ __('No undeposited receipts available.') }}</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="q2-sec-actions">
                            <button type="submit" class="q2-btn q2-btn--cta" @if($undepositedLines->isEmpty()) disabled @endif>{{ __('Record Deposit') }}</button>
                        </div>
                    </form>
                </div>

                <div class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Banking') }}</div>
                            <a href="{{ route('accounting.banking.dashboard') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 4l9 6.5M5 9v11h14V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Banking Centre') }}</a>
                            <a href="{{ route('accounting.banking.deposits') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5m0 0l-6 6m6-6l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Deposits') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
