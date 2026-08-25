<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-opt-tag">{{ __('11') }} &middot; {{ __('Tax Payments') }} ({{ __('PAYABLE') }} &rarr; {{ __('PAID') }})</div>

        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Payments') }}</h1>
                <p class="sub">{{ __('Record remittances to the authority and clear tax liabilities.') }}</p>
            </div>
        </div>

        <div class="tx-grid2">
            {{-- Payments Register --}}
            <div class="tx-card">
                <div class="tx-card-h">
                    <span class="ic">&#127974;</span>
                    <h2>{{ __('Payments Register') }}</h2>
                </div>
                <div class="tx-li-wrap">
                    <table class="tx-table" style="min-width:760px">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Tax') }}</th>
                                <th>{{ __('Period') }}</th>
                                <th class="num">{{ __('Amount') }}</th>
                                <th>{{ __('Bank') }}</th>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                @php
                                    $tchipClass = match ($payment->taxType?->category) {
                                        'WHT' => 'tx-t-wht',
                                        'PAYE' => 'tx-t-paye',
                                        'FBT' => 'tx-t-fbt',
                                        default => 'tx-t-vat',
                                    };
                                    $statusClass = match (strtolower($payment->status ?? '')) {
                                        'paid', 'confirmed' => 'tx-b-ok',
                                        'pending' => 'tx-b-pend',
                                        default => 'tx-b-off',
                                    };
                                    $statusLabel = match (strtolower($payment->status ?? '')) {
                                        'paid', 'confirmed' => __('Paid'),
                                        'pending' => __('Payable'),
                                        default => $payment->status,
                                    };
                                @endphp
                                <tr>
                                    <td class="tx-em">{{ $payment->payment_date?->format('d M Y') ?? '&mdash;' }}</td>
                                    <td><span class="tx-tchip {{ $tchipClass }}">{{ $payment->taxType?->code ?? '&mdash;' }}</span></td>
                                    <td class="tx-em">{{ $payment->period?->label ?? '&mdash;' }}</td>
                                    <td class="num"><strong>{{ number_format((float) $payment->amount, 2) }}</strong></td>
                                    <td class="tx-em">{{ $payment->bankAccount?->name ?? '&mdash;' }}</td>
                                    <td class="tx-mono">{{ $payment->payment_ref ?? '&mdash;' }}</td>
                                    <td><span class="tx-badge {{ $statusClass }}"><span class="bdot"></span>{{ $statusLabel }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No payments recorded yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Record Payment --}}
            <div class="tx-card">
                <div class="tx-card-h">
                    <span class="ic">&#129534;</span>
                    <h2>{{ __('Record Payment') }}</h2>
                </div>
                <div class="tx-pad">
                    <form method="POST" action="{{ route('accounting.taxation.payments.store') }}" id="tax-payment-form">
                        @csrf
                        <div class="tx-g3">
                            <div class="tx-f">
                                <label>{{ __('Tax Type') }} <span style="color:#B91C1C">*</span></label>
                                <select name="tax_type_id" class="in" required>
                                    <option value="">&mdash;</option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type->id }}" @selected(old('tax_type_id') == $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('tax_type_id')<p class="tx-exc">{{ $message }}</p>@enderror
                            </div>
                            <div class="tx-f">
                                <label>{{ __('Tax Period') }} <span style="color:#B91C1C">*</span></label>
                                <select name="period_id" class="in" required>
                                    <option value="">&mdash;</option>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period->id }}" @selected(old('period_id') == $period->id)>{{ $period->label }}</option>
                                    @endforeach
                                </select>
                                @error('period_id')<p class="tx-exc">{{ $message }}</p>@enderror
                            </div>
                            <div class="tx-f">
                                <label>{{ __('Amount') }} <span style="color:#B91C1C">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="in" required placeholder="0.00" style="text-align:right">
                                @error('amount')<p class="tx-exc">{{ $message }}</p>@enderror
                            </div>
                            <div class="tx-f">
                                <label>{{ __('Payment Date') }} <span style="color:#B91C1C">*</span></label>
                                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="in" required>
                                @error('payment_date')<p class="tx-exc">{{ $message }}</p>@enderror
                            </div>
                            <div class="tx-f">
                                <label>{{ __('Bank Account') }}</label>
                                <select name="bank_account_id" class="in">
                                    <option value="">&mdash;</option>
                                    @foreach ($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}" @selected(old('bank_account_id') == $bankAccount->id)>{{ $bankAccount->code }} &middot; {{ $bankAccount->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_account_id')<p class="tx-exc">{{ $message }}</p>@enderror
                            </div>
                            <div class="tx-f">
                                <label>{{ __('Tax Authority') }}</label>
                                <input type="text" name="authority" value="{{ old('authority') }}" class="in" placeholder="{{ __('Malawi Revenue Authority') }}">
                                @error('authority')<p class="tx-exc">{{ $message }}</p>@enderror
                            </div>
                            <div class="tx-f">
                                <label>{{ __('Payment Reference') }}</label>
                                <input type="text" name="payment_ref" value="{{ old('payment_ref') }}" class="in" placeholder="{{ __('e.g. PAY-003') }}">
                                @error('payment_ref')<p class="tx-exc">{{ $message }}</p>@enderror
                            </div>
                            <div class="tx-f">
                                <label>{{ __('Receipt Number') }}</label>
                                <input type="text" name="receipt_number" value="{{ old('receipt_number') }}" class="in" placeholder="{{ __('Authority receipt') }}">
                                @error('receipt_number')<p class="tx-exc">{{ $message }}</p>@enderror
                            </div>
                            <div class="tx-f">
                                <label>{{ __('Recorded By') }}</label>
                                <input type="text" value="{{ auth()->user()?->name ?? '&mdash;' }}" class="in" disabled style="background:var(--hair,#EEF3F1);color:var(--muted,#5f7476)">
                            </div>
                        </div>
                    </form>
                    <p class="tx-note">{{ __('On save: Dr Tax Payable &middot; Cr Bank &mdash; liability cleared and period status moves PAYABLE &rarr; PAID.') }}</p>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px">
                        <a href="{{ route('accounting.taxation.payments') }}" class="tx-btn tx-btn-ghost">{{ __('Cancel') }}</a>
                        <button type="submit" form="tax-payment-form" class="tx-btn tx-btn-cta">{{ __('Save Payment') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
