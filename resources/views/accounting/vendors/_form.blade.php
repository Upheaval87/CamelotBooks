@php
    $vendor = $vendor ?? null;
    $isEdit = $isEdit ?? (bool) $vendor;
    $formAction = $formAction ?? ($isEdit ? route('accounting.vendors.update', $vendor) : route('accounting.vendors.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? route('accounting.vendors.index');
    $title = $title ?? ($isEdit ? __('Edit Vendor') : __('Create Vendor'));
    $subtitle = $subtitle ?? 'Store contact details, payment terms and opening balances for this vendor.';
    $submitLabel = $submitLabel ?? ($isEdit ? __('Update Vendor') : __('Create Vendor'));

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');

    $cur = $isEdit ? old('currency', $vendor->currency ?? $cs) : old('currency', $cs);
    $terms = $isEdit ? old('payment_terms', $vendor->payment_terms ?? '') : old('payment_terms', '');
    $termOptions = [
        'due_on_receipt' => 'Due on Receipt',
        'net_15' => 'Net 15',
        'net_30' => 'Net 30',
        'net_60' => 'Net 60',
        'net_90' => 'Net 90',
        'custom' => 'Custom',
    ];

    $totalBilled = $isEdit ? (float) $vendor->bills->sum('amount') : 0.0;
    $balanceDue = $isEdit ? (float) $vendor->balance_due : 0.0;
@endphp

<div class="suite">

    {{-- sticky page head --}}
    <div class="sticky-head">
        <div>
            <h1>{{ $title }}</h1>
            <div class="sub">{{ $subtitle }}</div>
        </div>
        <div class="tbtns">
            @if($isEdit)
                <form method="POST" action="{{ route('accounting.vendors.toggle', $vendor) }}" id="vendor-archive-form" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to deactivate this vendor?') }}', { type: 'danger' })">
                    @csrf @method('PATCH')
                </form>
                <button type="submit" form="vendor-archive-form" class="btn danger-o sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 1 0-4h14a2 2 0 1 1 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8m-9 4h4"/></svg>
                    {{ $vendor->is_active ? __('Deactivate') : __('Activate') }}
                </button>
            @endif
            <a href="{{ $cancelRoute }}" class="btn ghost sm">{{ __('Cancel') }}</a>
            <button type="submit" form="vendor-form" class="btn cta">{{ $submitLabel }}</button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="vendor-form" novalidate>
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="shell">
            <div class="flex flex-col gap-5 min-w-0">

                {{-- contact --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5"/></svg></span>
                        <h2>Contact Information</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field sp2">
                            <label for="name">Name <span class="req">*</span></label>
                            <input id="name" name="name" type="text" class="input" value="{{ $isEdit ? old('name', $vendor->name) : old('name') }}" placeholder="e.g. John Banda" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="display_name">Display Name</label>
                            <input id="display_name" name="display_name" type="text" class="input" value="{{ $isEdit ? old('display_name', $vendor->display_name) : old('display_name') }}" placeholder="e.g. J. Banda Trading" />
                            <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" class="input" value="{{ $isEdit ? old('email', $vendor->email) : old('email') }}" placeholder="name@company.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="phone">Phone</label>
                            <input id="phone" name="phone" type="text" class="input" value="{{ $isEdit ? old('phone', $vendor->phone) : old('phone') }}" placeholder="+265 99 123 4567" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="billing_address">Billing Address</label>
                            <textarea id="billing_address" name="billing_address" rows="2" class="input">{{ $isEdit ? old('billing_address', $vendor->billing_address) : old('billing_address') }}</textarea>
                            <x-input-error :messages="$errors->get('billing_address')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="remit_to_address">Remit To Address</label>
                            <textarea id="remit_to_address" name="remit_to_address" rows="2" class="input">{{ $isEdit ? old('remit_to_address', $vendor->remit_to_address) : old('remit_to_address') }}</textarea>
                            <x-input-error :messages="$errors->get('remit_to_address')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- terms & accounting --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                        <h2>Terms &amp; Accounting</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g3">
                        <div class="field">
                            <label for="currency">Currency</label>
                            <input id="currency" name="currency" type="text" class="input" value="{{ $cur }}" maxlength="10" placeholder="MWK" />
                            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="payment_terms">Payment Terms</label>
                            <select id="payment_terms" name="payment_terms" class="input">
                                <option value="">Select terms…</option>
                                @foreach ($termOptions as $key => $label)
                                    <option value="{{ $key }}" {{ $terms === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="payment_terms_days">Payment Terms (Days)</label>
                            <input id="payment_terms_days" name="payment_terms_days" type="number" min="0" class="input" value="{{ $isEdit ? old('payment_terms_days', $vendor->payment_terms_days) : old('payment_terms_days') }}" placeholder="e.g. 30" />
                            <x-input-error :messages="$errors->get('payment_terms_days')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="opening_balance">Opening Balance ({{ $cs }})</label>
                            <input id="opening_balance" name="opening_balance" type="number" step="0.01" class="input" value="{{ $isEdit ? old('opening_balance', $vendor->opening_balance) : old('opening_balance', '0.00') }}" />
                            <x-input-error :messages="$errors->get('opening_balance')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="opening_balance_date">Opening Balance Date</label>
                            <input id="opening_balance_date" name="opening_balance_date" type="date" class="input" value="{{ $isEdit ? old('opening_balance_date', $vendor->opening_balance_date?->format('Y-m-d')) : old('opening_balance_date') }}" />
                            <x-input-error :messages="$errors->get('opening_balance_date')" class="mt-2" />
                        </div>
                    </div>
                </section>
            </div>

            {{-- right rail --}}
            <aside>
                <div class="railsum">
                    <div class="card">
                        @if($isEdit)
                            <div class="rail-sec">
                                <div class="sec-head">
                                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                                    <h2>Balances</h2>
                                    <span class="rule"></span>
                                </div>
                                <div class="vlist" style="margin-top:12px">
                                    <div class="srow"><span class="l">Opening Balance</span><span class="v">{{ format_number($vendor->opening_balance ?? 0) }}</span></div>
                                    <div class="srow"><span class="l">Total Billed</span><span class="v">{{ format_number($totalBilled) }}</span></div>
                                </div>
                                <div class="gt"><span class="l">Open Balance</span><span class="v">{{ format_number($balanceDue) }}</span></div>
                            </div>
                        @endif

                        <div class="rail-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                                <h2>Quick Nav</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="vlist">
                                @if($isEdit)
                                    <a href="{{ route('accounting.vendors.show', $vendor) }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></span>
                                        View Vendor
                                    </a>
                                    <a href="{{ route('accounting.bills.create', ['vendor_id' => $vendor->id]) }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6"/></svg></span>
                                        New Bill
                                    </a>
                                    <a href="{{ route('accounting.vendor-payments.create', ['vendor_id' => $vendor->id]) }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg></span>
                                        Record Payment
                                    </a>
                                    <a href="{{ route('accounting.vendors.index') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg></span>
                                        Back to Vendors
                                    </a>
                                @else
                                    <a href="{{ route('accounting.vendors.index') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg></span>
                                        Vendors List
                                    </a>
                                    <a href="{{ route('accounting.aging.ap-summary') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                                        Vendor Balances
                                    </a>
                                    <a href="{{ route('accounting.reports.purchases-by-vendor') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3.055A9.001 9.001 0 1 0 20.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0 1 20.488 9z"/></svg></span>
                                        Purchases by Vendor
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>
