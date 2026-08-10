<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $remaining = (float) $creditNote->amount - (float) $creditNote->amount_applied;
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- sticky head --}}
            <div class="sticky-head">
                <div>
                    <h1>{{ __('Apply Credit') }} <span class="mono-chip">{{ $creditNote->credit_note_number }}</span></h1>
                    <div class="sub">{{ $creditNote->customer->name ?? __('—') }} · {{ __('available') }} {{ $cs }}{{ format_number($availableAmount) }}</div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.credit-notes.show', $creditNote) }}" class="btn ghost sm">{{ __('Cancel') }}</a>
                    <button type="submit" form="cn-apply-form" class="btn cta">{{ __('Apply Credit') }}</button>
                </div>
            </div>

            <x-input-error :messages="$errors->get('error')" class="mb-4" />

            <form method="POST" action="{{ route('accounting.credit-notes.apply', $creditNote) }}" id="cn-apply-form" novalidate>
                @csrf

                <div class="shell" style="grid-template-columns:minmax(0,1fr)">
                    <div class="card">
                        <div class="card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                <h2>{{ __('Apply to Open Invoice') }}</h2>
                                <span class="rule"></span>
                            </div>

                            @forelse($openInvoices as $invoice)
                                <div class="g4" style="border:1px solid var(--line,#E2ECEC);border-radius:12px;padding:14px;margin-bottom:12px">
                                    <div class="field sp2">
                                        <label><input type="radio" name="invoice_id" value="{{ $invoice->id }}" required class="mr-2" @if($loop->first) checked @endif /> Invoice <a href="{{ route('accounting.invoices.show', $invoice) }}" class="mono" style="color:var(--sec,#128F8E)">{{ $invoice->invoice_number }}</a></label>
                                        <span class="hint">{{ $invoice->customer?->name ?? '—' }} · due {{ $invoice->due_date?->format('M d, Y') ?? '—' }} · open {{ $cs }}{{ format_number($invoice->amount - $invoice->amount_paid) }}</span>
                                    </div>
                                    <div class="field">
                                        <label>{{ __('Amount') }} ({{ $cs }})</label>
                                        <input type="number" name="amount" min="0.01" max="{{ min($remaining, $invoice->amount - $invoice->amount_paid) }}" step="0.01" class="input" value="{{ min($remaining, $invoice->amount - $invoice->amount_paid) }}" />
                                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                                    </div>
                                </div>
                            @empty
                                <div class="empty" style="padding:26px;text-align:center">{{ __('No open invoices for this customer.') }}</div>
                            @endforelse

                            <x-input-error :messages="$errors->get('invoice_id')" class="mt-2" />

                            <div class="li-totals" style="margin-top:14px"><div class="box" style="max-width:20rem;margin-left:auto">
                                <div class="trow"><span>{{ __('Available') }}</span><span class="v">{{ $cs }}{{ format_number($availableAmount) }}</span></div>
                                <div class="trow total"><span>{{ __('Total') }}</span><span class="v">{{ $cs }}{{ format_number($remaining) }}</span></div>
                            </div></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
