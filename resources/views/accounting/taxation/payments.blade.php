<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Payments') }}</h1>
                <p class="sub">{{ __('Record remittances to tax authorities and track what has been settled.') }}</p>
            </div>
        </div>

        @php
            $totalPaid = $payments->where('status', 'PAID')->sum('amount');
            $totalPending = $payments->where('status', 'PENDING')->sum('amount');
            [$pendClass] = ['tx-b-pend'];
        @endphp

        <div class="tx-kpis" style="grid-template-columns:repeat(3, 1fr);">
            <div class="tx-kpi hero">
                <div class="l">{{ __('Total Paid') }}</div>
                <div class="v">{{ number_format($totalPaid, 2) }}</div>
                <div class="n">({{ $cs }})</div>
            </div>
            <div class="tx-kpi {{ $totalPending > 0 ? 'warn' : '' }}">
                <div class="l">{{ __('Pending Payments') }}</div>
                <div class="v">{{ number_format($totalPending, 2) }}</div>
                <div class="n">({{ $cs }})</div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('Payments Recorded') }}</div>
                <div class="v">{{ $payments->count() }}</div>
                <div class="n">{{ __('all statuses') }}</div>
            </div>
        </div>

        <div class="tx-card">
            <div class="tx-card-h">
                <span class="ic">&#43;</span>
                <h2>{{ __('Record a Payment') }}</h2>
            </div>
            <form method="POST" action="{{ route('accounting.taxation.payments.store') }}" class="tx-pad tx-form-grid" id="tax-payment-form">
                @csrf
                <div>
                    <label for="tp-tax-type" class="tx-lbl">{{ __('Tax Type') }} <span style="color:#B91C1C;">*</span></label>
                    <select id="tp-tax-type" name="tax_type_id" class="tx-ddl" required>
                        <option value="">{{ __('Choose&hellip;') }}</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('tax_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('tax_type_id')<p class="tx-exc">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tp-period" class="tx-lbl">{{ __('Tax Period') }} <span style="color:#B91C1C;">*</span></label>
                    <select id="tp-period" name="period_id" class="tx-ddl" required>
                        <option value="">{{ __('Choose&hellip;') }}</option>
                        @foreach ($types as $groupType)
                            <optgroup label="{{ $groupType->name }}">
                                @foreach ($periods->where('tax_type_id', $groupType->id) as $groupPeriod)
                                    <option value="{{ $groupPeriod->id }}" @selected(old('period_id') == $groupPeriod->id)>{{ $groupPeriod->label }} &middot; {{ $groupPeriod->start_date->format('M Y') }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('period_id')<p class="tx-exc">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tp-amount" class="tx-lbl">{{ __('Amount') }} <span style="color:#B91C1C;">*</span></label>
                    <input type="number" step="0.01" min="0.01" id="tp-amount" name="amount" value="{{ old('amount') }}" class="tx-inp-sm numr" required placeholder="0.00">
                    @error('amount')<p class="tx-exc">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tp-date" class="tx-lbl">{{ __('Payment Date') }} <span style="color:#B91C1C;">*</span></label>
                    <input type="date" id="tp-date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="tx-inp-sm" required>
                    @error('payment_date')<p class="tx-exc">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tp-bank" class="tx-lbl">{{ __('Paid From (Bank Account)') }} <span style="color:#B91C1C;">*</span></label>
                    <select id="tp-bank" name="bank_account_id" class="tx-ddl">
                        <option value="">{{ __('Choose&hellip;') }}</option>
                        @foreach ($bankAccounts as $bankAccount)
                            <option value="{{ $bankAccount->id }}" @selected(old('bank_account_id') == $bankAccount->id)>{{ $bankAccount->code }} &middot; {{ $bankAccount->name }}</option>
                        @endforeach
                    </select>
                    @error('bank_account_id')<p class="tx-exc">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tp-authority" class="tx-lbl">{{ __('Authority Account') }}</label>
                    <input type="text" id="tp-authority" name="authority" value="{{ old('authority') }}" class="tx-inp-sm" placeholder="{{ __('e.g. MRA — Domestic Taxes') }}">
                    @error('authority')<p class="tx-exc">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tp-ref" class="tx-lbl">{{ __('Payment Reference') }}</label>
                    <input type="text" id="tp-ref" name="payment_ref" value="{{ old('payment_ref') }}" class="tx-inp-sm" placeholder="{{ __('bank reference / slip no.') }}">
                    @error('payment_ref')<p class="tx-exc">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tp-receipt" class="tx-lbl">{{ __('Receipt Number') }}</label>
                    <input type="text" id="tp-receipt" name="receipt_number" value="{{ old('receipt_number') }}" class="tx-inp-sm" placeholder="{{ __('official receipt from authority') }}">
                    @error('receipt_number')<p class="tx-exc">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tp-user" class="tx-lbl">{{ __('Recorded By') }}</label>
                    <input type="text" id="tp-user" value="{{ auth()->user()?->name ?? '—' }}" class="tx-inp-sm" disabled>
                </div>
                <div style="align-self:end;">
                    <button type="submit" class="tx-btn tx-btn-cta">{{ __('Record Payment') }}</button>
                </div>
            </form>
        </div>

        <div class="tx-card">
            <div class="tx-card-h">
                <span class="ic">&#9632;</span>
                <h2>{{ __('Payment History') }} <span style="color:var(--muted);font-weight:600;">({{ $cs }})</span></h2>
            </div>
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:940px;">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Tax Type') }}</th>
                            <th>{{ __('Period') }}</th>
                            <th>{{ __('Authority') }}</th>
                            <th class="num">{{ __('Amount') }}</th>
                            <th>{{ __('Receipt #') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                                <td class="tx-mono">{{ $payment->payment_ref ?? '&mdash;' }}</td>
                                <td><span class="tx-tchip tx-t-vat">{{ $payment->taxType?->name ?? '&mdash;' }}</span></td>
                                <td class="tx-em">{{ $payment->taxPeriod?->label ?? '&mdash;' }}</td>
                                <td class="tx-em">{{ $payment->authority ?: '&mdash;' }}</td>
                                <td class="num"><strong>{{ number_format((float) $payment->amount, 2) }}</strong></td>
                                <td class="tx-mono tx-em">{{ $payment->receipt_number ?? '&mdash;' }}</td>
                                <td>
                                    @if ($payment->status === 'PAID')
                                        <span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('Paid') }}</span>
                                    @else
                                        <span class="tx-badge tx-b-pend"><span class="bdot"></span>{{ __('Pending') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No payments recorded yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
