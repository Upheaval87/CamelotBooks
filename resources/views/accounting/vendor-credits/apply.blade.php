<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $remaining = (float) $vendorCredit->amount - (float) $vendorCredit->amount_applied;
    @endphp

    <div class="suite py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- sticky head --}}
            <div class="sticky-head">
                <div>
                    <h1>{{ __('Apply Credit') }} <span class="mono-chip">{{ $vendorCredit->credit_note_number }}</span></h1>
                    <div class="sub">{{ $vendorCredit->vendor->name ?? __('—') }} · {{ __('available') }} {{ $cs }}{{ format_number($availableAmount) }}</div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.vendor-credits.show', $vendorCredit) }}" class="btn ghost sm">{{ __('Cancel') }}</a>
                    <button type="submit" form="vc-apply-form" class="btn cta">{{ __('Apply Credit') }}</button>
                </div>
            </div>

            <x-input-error :messages="$errors->get('error')" class="mb-4" />

            <form method="POST" action="{{ route('accounting.vendor-credits.apply', $vendorCredit) }}" id="vc-apply-form" novalidate>
                @csrf

                <div class="shell" style="grid-template-columns:minmax(0,1fr)">
                    <div class="card">
                        <div class="card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                <h2>{{ __('Apply to Open Bill') }}</h2>
                                <span class="rule"></span>
                            </div>

                            @forelse($openBills as $bill)
                                <div class="g4" style="border:1px solid var(--line,#E2ECEC);border-radius:12px;padding:14px;margin-bottom:12px">
                                    <div class="field sp2">
                                        <label><input type="radio" name="bill_id" value="{{ $bill->id }}" required class="mr-2" @if($loop->first) checked @endif /> Bill <a href="{{ route('accounting.bills.show', $bill) }}" class="mono" style="color:var(--sec,#128F8E)">{{ $bill->bill_number }}</a></label>
                                        <span class="hint">{{ $bill->vendor?->name ?? '—' }} · due {{ $bill->due_date?->format('M d, Y') ?? '—' }} · open {{ $cs }}{{ format_number($bill->amount - $bill->amount_paid) }}</span>
                                    </div>
                                    <div class="field">
                                        <label>{{ __('Amount') }} ({{ $cs }})</label>
                                        <input type="number" name="amount" min="0.01" max="{{ min($remaining, $bill->amount - $bill->amount_paid) }}" step="0.01" class="input" value="{{ min($remaining, $bill->amount - $bill->amount_paid) }}" />
                                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                                    </div>
                                </div>
                            @empty
                                <div class="empty" style="padding:26px;text-align:center">{{ __('No open bills for this vendor.') }}</div>
                            @endforelse

                            <x-input-error :messages="$errors->get('bill_id')" class="mt-2" />

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
