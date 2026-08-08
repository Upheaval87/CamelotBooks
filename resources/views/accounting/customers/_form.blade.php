@php
    $customer = $customer ?? null;
    $isEdit = $isEdit ?? (bool) $customer;
    $formAction = $formAction ?? ($isEdit ? route('accounting.customers.update', $customer) : route('accounting.customers.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? route('accounting.customers.index');
    $title = $title ?? ($isEdit ? __('Edit Customer') : __('Create Customer'));
    $subtitle = $subtitle ?? 'Store contact details, payment terms and opening balances for this customer.';
    $submitLabel = $submitLabel ?? ($isEdit ? __('Update Customer') : __('Create Customer'));

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');

    $cur = $isEdit ? old('currency', $customer->currency ?? $cs) : old('currency', $cs);
    $terms = $isEdit ? old('payment_terms', $customer->payment_terms ?? '') : old('payment_terms', '');
    $termOptions = [
        'due_on_receipt' => 'Due on Receipt',
        'net_15' => 'Net 15',
        'net_30' => 'Net 30',
        'net_60' => 'Net 60',
        'net_90' => 'Net 90',
        'custom' => 'Custom',
    ];

    $secIc = 'w-7 h-7 rounded-[9px] grid place-items-center text-white bg-[#128F8E] shadow-[inset_0_1px_0_rgba(255,255,255,.18),0_3px_8px_-3px_rgba(10,80,80,.4)]';
    $btnGhost = 'inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl font-semibold text-[13.5px] border border-shell bg-white/85 text-gray-700 shadow-sm transition-all duration-150 hover:bg-[rgba(17,69,75,.06)] hover:border-navy-700/25 hover:text-[#0B2A2D] hover:-translate-y-px active:translate-y-0';
    $btnPrimary = 'inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl font-semibold text-[13.5px] text-white border border-white/25 bg-gradient-to-b from-gold-500 to-gold-600 shadow-new transition-all duration-150 hover:-translate-y-px active:translate-y-0';
@endphp

<div>
    {{-- sticky page head --}}
    <div class="form-page-head flex items-start justify-between gap-4 flex-wrap pb-4 mb-6 border-b border-line">
        <div>
            <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">{{ $title }}</h1>
            <p class="mt-1 text-[13.5px] text-gray-500">{{ $subtitle }}</p>
        </div>
        <div class="flex gap-2.5 flex-wrap items-center">
            <a href="{{ $cancelRoute }}" class="{{ $btnGhost }}">{{ __('Cancel') }}</a>
            <button type="submit" form="customer-form" class="{{ $btnPrimary }}">{{ $submitLabel }}</button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="customer-form" novalidate>
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="grid gap-6 items-start lg:grid-cols-[1fr_340px]">
            <div class="flex flex-col gap-5 min-w-0">

                {{-- contact --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="x-sec">
                        <span class="x-sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5"/></svg></span>
                        <h2 class="x-sec-h2">{{ __('Contact Information') }}</h2>
                        <span class="x-sec-rule"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-5 gap-y-4 mt-2">
                        <div>
                            <label for="name" class="input-label">{{ __('Name') }} <span class="text-red-600">*</span></label>
                            <input id="name" name="name" type="text" class="x-input" value="{{ $isEdit ? old('name', $customer->name) : old('name') }}" placeholder="e.g. Jane Mwale" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <label for="display_name" class="input-label">{{ __('Display Name') }}</label>
                            <input id="display_name" name="display_name" type="text" class="x-input" value="{{ $isEdit ? old('display_name', $customer->display_name) : old('display_name') }}" placeholder="e.g. J. Mwale Trading" />
                            <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                        </div>
                        <div>
                            <label for="email" class="input-label">{{ __('Email') }}</label>
                            <input id="email" name="email" type="email" class="x-input" value="{{ $isEdit ? old('email', $customer->email) : old('email') }}" placeholder="name@company.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <label for="phone" class="input-label">{{ __('Phone') }}</label>
                            <input id="phone" name="phone" type="text" class="x-input" value="{{ $isEdit ? old('phone', $customer->phone) : old('phone') }}" placeholder="+265 99 123 4567" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div class="col-span-2">
                            <label for="billing_address" class="input-label">{{ __('Billing Address') }}</label>
                            <textarea id="billing_address" name="billing_address" rows="2" class="x-input x-textarea">{{ $isEdit ? old('billing_address', $customer->billing_address) : old('billing_address') }}</textarea>
                            <x-input-error :messages="$errors->get('billing_address')" class="mt-2" />
                        </div>
                        <div class="col-span-2">
                            <label for="shipping_address" class="input-label">{{ __('Shipping Address') }}</label>
                            <textarea id="shipping_address" name="shipping_address" rows="2" class="x-input x-textarea">{{ $isEdit ? old('shipping_address', $customer->shipping_address) : old('shipping_address') }}</textarea>
                            <x-input-error :messages="$errors->get('shipping_address')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- terms & accounting --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="x-sec">
                        <span class="x-sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                        <h2 class="x-sec-h2">{{ __('Terms & Accounting') }}</h2>
                        <span class="x-sec-rule"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-5 gap-y-4 mt-2">
                        <div>
                            <label for="currency" class="input-label">{{ __('Currency') }}</label>
                            <input id="currency" name="currency" type="text" class="x-input" value="{{ $cur }}" maxlength="10" placeholder="MWK" />
                            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                        </div>
                        <div class="x-select-wrap">
                            <label for="payment_terms" class="input-label">{{ __('Payment Terms') }}</label>
                            <select id="payment_terms" name="payment_terms" class="x-input">
                                <option value="">Select terms…</option>
                                @foreach ($termOptions as $key => $label)
                                    <option value="{{ $key }}" {{ $terms === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <svg class="x-select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                            <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
                        </div>
                        <div>
                            <label for="payment_terms_days" class="input-label">{{ __('Payment Terms (Days)') }}</label>
                            <input id="payment_terms_days" name="payment_terms_days" type="number" min="0" class="x-input" value="{{ $isEdit ? old('payment_terms_days', $customer->payment_terms_days) : old('payment_terms_days') }}" placeholder="e.g. 30" />
                            <x-input-error :messages="$errors->get('payment_terms_days')" class="mt-2" />
                        </div>
                        <div>
                            <label for="credit_limit" class="input-label">{{ __('Credit Limit') }} ({{ $cs }})</label>
                            <input id="credit_limit" name="credit_limit" type="number" step="0.01" min="0" class="x-input" value="{{ $isEdit ? old('credit_limit', $customer->credit_limit) : old('credit_limit') }}" placeholder="0.00" />
                            <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
                        </div>
                        <div>
                            <label for="opening_balance" class="input-label">{{ __('Opening Balance') }} ({{ $cs }})</label>
                            <input id="opening_balance" name="opening_balance" type="number" step="0.01" class="x-input" value="{{ $isEdit ? old('opening_balance', $customer->opening_balance) : old('opening_balance', '0.00') }}" />
                            <x-input-error :messages="$errors->get('opening_balance')" class="mt-2" />
                        </div>
                        <div>
                            <label for="opening_balance_date" class="input-label">{{ __('Opening Balance Date') }}</label>
                            <input id="opening_balance_date" name="opening_balance_date" type="date" class="x-input" value="{{ $isEdit ? old('opening_balance_date', $customer->opening_balance_date?->format('Y-m-d')) : old('opening_balance_date') }}" />
                            <x-input-error :messages="$errors->get('opening_balance_date')" class="mt-2" />
                        </div>
                    </div>
                </section>
            </div>

            {{-- right rail --}}
            <aside class="x-rail-wrap">
                <div class="x-rail">
                    <nav class="x-rail-card">
                        <div class="x-rail-title">{{ __('Quick Links') }}</div>
                        <div class="x-rail-nav">
                            <a href="{{ route('accounting.invoices.create') }}" class="x-rail-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6"/></svg>
                                {{ __('New Invoice') }}
                            </a>
                            <a href="{{ route('accounting.customer-payments.create') }}" class="x-rail-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                                {{ __('Record Payment') }}
                            </a>
                            <a href="{{ route('accounting.customers.index') }}" class="x-rail-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                                {{ __('All Customers') }}
                            </a>
                        </div>
                    </nav>
                </div>
            </aside>
        </div>
    </form>
</div>
