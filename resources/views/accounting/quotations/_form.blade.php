@php
    $quotation = $quotation ?? null;
    $isEdit = $isEdit ?? (bool) $quotation;
    $formAction = $formAction ?? ($isEdit ? route('accounting.quotations.update', $quotation) : route('accounting.quotations.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? ($isEdit ? route('accounting.quotations.show', $quotation) : route('accounting.quotations.index'));
    $title = $title ?? ($isEdit ? 'Edit Quotation' : 'Create Quotation');
    $submitLabel = $submitLabel ?? ($isEdit ? 'Save Changes' : 'Save');

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $defaultIncomeAccountId = $defaultIncomeAccountId ?? ($incomeAccounts->first()?->id ?? '');

    $oldCustomerId = old('customer_id', $quotation?->customer_id ?? ($selectedCustomerId ?? ''));
    $selectedCustomer = $customers->firstWhere('id', (int) $oldCustomerId);

    $initialMode = old('customer_type', ($quotation && ! $quotation->customer_id && $quotation->customer_name) ? 'walkin' : 'existing');
    $termsRaw = strtolower((string) old('terms_key', $quotation?->terms_key ?? $selectedCustomer?->payment_terms ?? 'net30'));
    $termsMap = ['net-30' => 'net30', 'net-15' => 'net15', 'net-60' => 'net60', 'net-7' => 'net7', 'due-on-receipt' => 'receipt', 'receipt' => 'receipt'];
    $selectedTerms = $termsMap[$termsRaw] ?? 'net30';
    $selContact = old('contact_person', $quotation?->contact_person ?? $selectedCustomer?->display_name ?? $selectedCustomer?->name ?? '');
    $selEmail = old('email', $quotation?->email ?? $selectedCustomer?->email ?? '');
    $selPhone = old('phone', $quotation?->phone ?? $selectedCustomer?->phone ?? '');

    $currencyCodes = $currencies->pluck('code')->all();
    $defaultCurrency = in_array('MWK', $currencyCodes) ? 'MWK' : ($currencies->first()?->code ?? 'USD');
    $selectedCurrency = old('currency', $quotation?->currency ?? $defaultCurrency);
    $selectedBranchId = old('branch_id', $quotation?->branch_id ?? $branches->first()?->id ?? '');
    $selectedCostCenterId = old('cost_center_id', $quotation?->cost_center_id ?? '');
    $selectedCostCenterLabel = $selectedCostCenterId ? ($costCenters->firstWhere('id', (int) $selectedCostCenterId)?->name ?? '') : '';

    $quotationAttachments = $quotationAttachments ?? ($quotation?->attachments ?? collect());

    if ($isEdit) {
        $customerLine = $quotation?->customer?->name ?? __('No customer');
        $quotedDate = $quotation?->quotation_date?->format('M d, Y') ?? '—';
        $validDate = $quotation?->valid_until?->format('M d, Y') ?? '—';
        $subtitle = "{$customerLine} · " . __('quoted') . " {$quotedDate} · " . __('valid until') . " {$validDate}";
    } else {
        $subtitle = $subtitle ?? __('Quote products & services with a live summary.');
    }

    $linesData = [];
    if (old('lines')) {
        foreach (array_values(old('lines')) as $l) {
            $gross = ((float) ($l['quantity'] ?? 0)) * ((float) ($l['unit_price'] ?? 0));
            $flat = (float) ($l['discount'] ?? 0);
            $linesData[] = [
                'product_id' => $l['product_id'] ?? '',
                'label' => $products->firstWhere('id', (int) ($l['product_id'] ?? 0))?->name ?? '',
                'sku' => $products->firstWhere('id', (int) ($l['product_id'] ?? 0))?->sku ?? '',
                'description' => $l['description'] ?? '',
                'quantity' => (float) ($l['quantity'] ?? 1),
                'unit_price' => (float) ($l['unit_price'] ?? 0),
                'discount' => $flat,
                'discount_pct' => $gross > 0 ? round($flat / $gross * 100, 2) : 0,
                'tax_rate' => (float) ($l['tax_rate'] ?? 0),
                'income_account_id' => $l['income_account_id'] ?? $defaultIncomeAccountId,
            ];
        }
    } elseif ($quotation) {
        $linesData = $quotation->lines->map(function ($l) {
            $gross = ((float) $l->quantity) * ((float) $l->unit_price);
            $flat = (float) $l->discount;

            return [
                'product_id' => $l->product_id ?? '',
                'label' => $l->product?->name ?? '',
                'sku' => $l->product?->sku ?? '',
                'description' => $l->description,
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'discount' => $flat,
                'discount_pct' => $gross > 0 ? round($flat / $gross * 100, 2) : 0,
                'tax_rate' => (float) $l->tax_rate,
                'income_account_id' => $l->income_account_id,
            ];
        })->all();
    }
@endphp

<div class="q2">
    {{-- §2/§3 sticky page head --}}
    <div class="q2-head q2-head--sticky">
        <div class="min-w-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="q2-title">{{ $title }}</h1>
                @if ($isEdit)
                    <span class="q2-mono" style="padding:.25rem .625rem;border:1px solid var(--line,#E2ECEC);border-radius:.5rem;background:#fff">{{ $quotation->quotation_number }}</span>
                    <span class="q2-badge q2-badge--{{ $quotation->status }}"><span class="q2-dot"></span>{{ __(ucfirst($quotation->status)) }}</span>
                @endif
            </div>
            <p class="q2-sub">{{ $subtitle }}</p>
        </div>
        <div class="q2-head-actions">
            <a href="{{ $cancelRoute }}" class="q2-btn q2-btn--ghost">{{ __('Cancel') }}</a>
            @if($isEdit)
                @if($quotation->created_by && (int) $quotation->created_by !== (int) auth()->id())
                    <button type="submit" form="quotation-delete-form" class="q2-btn q2-btn--danger">{{ __('Delete') }}</button>
                @endif
                <div class="q2-seg">
                    <button type="submit" name="action" value="save" form="quotation-form" class="q2-btn q2-btn--sec">{{ $submitLabel }}</button>
                    <button type="submit" name="action" value="save_and_email" form="quotation-form" class="q2-btn q2-btn--cta">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20l4-9M4 20h16M8 11l8-8M8 11h7a1 1 0 011 1v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ __('Save & Email') }}
                    </button>
                </div>
            @else
                <button type="submit" name="submission_mode" value="draft" form="quotation-form" class="q2-btn q2-btn--sec">{{ __('Save Draft') }}</button>
                <button type="button" id="quot-submit-btn" class="q2-btn q2-btn--cta" disabled
                        title="{{ __('Requires customer name, contact person and at least one complete line (code, name, description, qty, unit price)') }}">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 17L17 7M9 7h8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ __('Submit') }}
                </button>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="quotation-form" class="q2-form" enctype="multipart/form-data" novalidate data-customer-name="{{ $selectedCustomer?->name ?? '' }}">
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif
        <input type="hidden" name="submission_mode" id="quot-submission-mode" value="" />

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="q2-shell q2-shell--form">
            <div class="q2-main">

                {{-- (a) customer --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Customer Information') }}</h2>
                    </div>
                    <div class="q2-g4 mt-5">
                        <div class="q2-field">
                            <label for="customer_type" class="q2-label">{{ __('Customer type') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                            <select id="ci-mode" name="customer_type" class="q2-select">
                                <option value="existing" @selected($initialMode === 'existing')>{{ __('Existing customer') }}</option>
                                <option value="walkin" @selected($initialMode === 'walkin')>{{ __('Walk-in customer') }}</option>
                            </select>
                        </div>
                        <div class="q2-field">
                            <label for="valid_until" class="q2-label">{{ __('Valid Until') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                            <input id="valid_until" name="valid_until" type="date" class="q2-input" value="{{ old('valid_until', $quotation?->valid_until?->format('Y-m-d') ?? '') }}" />
                            <x-input-error :messages="$errors->get('valid_until')" />
                        </div>
                        <div class="q2-field">
                            <label for="currency" class="q2-label">{{ __('Currency') }}</label>
                            <select id="currency" name="currency" class="q2-select">
                                @forelse ($currencies as $curOption)
                                    <option value="{{ $curOption->code }}" @selected($selectedCurrency === $curOption->code)>{{ $curOption->code }} - {{ $curOption->name }}</option>
                                @empty
                                    <option value="USD" @selected($selectedCurrency === 'USD')>USD - US Dollar</option>
                                @endforelse
                            </select>
                        </div>
                    </div>

                    <div class="q2-field mt-4" id="ci-search-wrap">
                        <label for="customer_id" class="q2-label">{{ __('Customer') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                        <x-scoped-search-field
                            name="customer_id"
                            entity="customer"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'customer']) }}"
                            :value="old('customer_id', $oldCustomerId)"
                            :label="old('customer_name', $selectedCustomer?->name ?? '')"
                            placeholder="{{ __('Search customers…') }}"
                            on-select="quotCustomerSelected"
                            required
                        />
                        <x-input-error :messages="$errors->get('customer_id')" />
                    </div>

                    <div class="q2-field mt-4" id="ci-name-wrap" style="display:none">
                        <label for="ci-name" class="q2-label">{{ __('Customer name') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                        <input id="ci-name" name="customer_name" type="text" class="q2-input" value="{{ old('customer_name', $quotation?->customer_name ?? '') }}" placeholder="{{ __('e.g. Jane Mwale') }}" />
                        <x-input-error :messages="$errors->get('customer_name')" />
                    </div>

                    <div class="q2-g4 mt-4">
                        <div class="q2-field">
                            <label for="quot-contact" class="q2-label">{{ __('Contact Person') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                            <input type="text" class="q2-input" id="quot-contact" name="contact_person" value="{{ $selContact }}" />
                            <x-input-error :messages="$errors->get('contact_person')" />
                        </div>
                        <div class="q2-field">
                            <label for="quot-email" class="q2-label">{{ __('Email') }}</label>
                            <input type="email" class="q2-input" id="quot-email" name="email" value="{{ $selEmail }}" />
                            <x-input-error :messages="$errors->get('email')" />
                        </div>
                        <div class="q2-field">
                            <label for="quot-phone" class="q2-label">{{ __('Phone') }}</label>
                            <input type="text" class="q2-input" id="quot-phone" name="phone" value="{{ $selPhone }}" />
                            <x-input-error :messages="$errors->get('phone')" />
                        </div>
                        <div class="q2-field">
                            <label for="quot-terms" class="q2-label">{{ __('Payment Terms') }}</label>
                            <select id="quot-terms" name="terms_key" class="q2-select">
                                <option value="receipt" @selected($selectedTerms === 'receipt')>{{ __('Due on receipt') }}</option>
                                <option value="net7" @selected($selectedTerms === 'net7')>{{ __('Net 7') }}</option>
                                <option value="net15" @selected($selectedTerms === 'net15')>{{ __('Net 15') }}</option>
                                <option value="net30" @selected($selectedTerms === 'net30')>{{ __('Net 30') }}</option>
                                <option value="net60" @selected($selectedTerms === 'net60')>{{ __('Net 60') }}</option>
                            </select>
                        </div>
                    </div>
                    <p class="q2-savenote" id="ci-save-note" style="display:none">{{ __('Walk-in captured — these details will be saved to the customer registry for future use.') }}</p>
                    <p class="q2-hint mt-4">{{ __('Contact details are captured with the quotation and saved to the customer registry for walk-in customers.') }}</p>
                </section>

                {{-- (b) quotation info --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Quotation Information') }}</h2>
                    </div>
                    <div class="q2-g4 mt-5">
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Quotation №') }}</label>
                            <input type="text" class="q2-input" value="{{ $quotation?->quotation_number ?? '' }}" placeholder="{{ __('Auto-assigned on save') }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label for="quotation_date" class="q2-label">{{ __('Date') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                            <input id="quotation_date" name="quotation_date" type="date" class="q2-input" value="{{ old('quotation_date', $quotation?->quotation_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('quotation_date')" />
                        </div>
                        <div class="q2-field">
                            <label for="reference" class="q2-label">{{ __('Reference №') }}</label>
                            <input id="reference" name="reference" type="text" class="q2-input" value="{{ old('reference', $quotation?->reference ?? '') }}" placeholder="{{ __('Optional') }}" />
                            <x-input-error :messages="$errors->get('reference')" />
                        </div>
                        <div class="q2-field">
                            <label for="branch_id" class="q2-label">{{ __('Branch') }}</label>
                            <select id="branch_id" name="branch_id" class="q2-select">
                                <option value="">-- {{ __('Select branch') }} --</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('branch_id')" />
                        </div>
                        <div class="q2-field">
                            <label for="cost_center_id" class="q2-label">{{ __('Cost Centre') }}</label>
                            <x-scoped-search-field
                                name="cost_center_id"
                                entity="cost-center"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                                :value="old('cost_center_id', $selectedCostCenterId)"
                                :label="old('cost_center_id', $selectedCostCenterLabel)"
                                placeholder="{{ __('None') }}"
                            />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Department') }}</label>
                            <input type="text" class="q2-input" placeholder="Operations" readonly />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Prepared By') }}</label>
                            <input type="text" class="q2-input" value="{{ $quotation?->createdByUser?->name ?? auth()->user()?->name ?? '' }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Project') }}</label>
                            <input type="text" class="q2-input" placeholder="{{ __('Optional') }}" readonly />
                        </div>
                    </div>
                    <p class="q2-hint mt-4">{{ __('Quotation № is auto-assigned on save. Department & Project are display-only.') }}</p>
                </section>

                {{-- (c) line items --}}
                <section class="q2-sec relative z-30">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Line Items') }}</h2>
                        <button type="button" id="quot-add-line" class="q2-btn q2-btn--ghost q2-btn--sm" style="margin-left:auto">＋ {{ __('Add Line') }}</button>
                    </div>

                    <div class="mt-4 border border-shell round-thead-clip bg-[#fbfcfe]">
                        <table id="quot-lines-table" class="q2-tbl w-full text-[0.929rem] table-fixed">
                            <thead>
                                <tr>
                                    <th style="width:12%">{{ __('Item Code') }}</th>
                                    <th style="width:24%">{{ __('Item Name') }}</th>
                                    <th style="width:24%">{{ __('Description') }}</th>
                                    <th style="width:7%" class="q2-right">{{ __('Qty') }}</th>
                                    <th style="width:11%" class="q2-right">{{ __('Unit Price') }} ({{ $cs }})</th>
                                    <th style="width:8%" class="q2-right">{{ __('Disc %') }}</th>
                                    <th style="width:10%" class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th style="width:4%"></th>
                                </tr>
                            </thead>
                            <tbody id="quot-lines-body"></tbody>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </section>

                {{-- (d) notes --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M4 10h16M4 15h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Notes') }}</h2>
                    </div>
                    <div class="q2-g2 mt-5">
                        <div class="q2-field">
                            <label for="memo" class="q2-label">{{ __('Customer Notes') }}</label>
                            <textarea id="memo" name="memo" class="q2-input" style="height:auto;min-height:6rem;padding:.75rem .875rem;resize:vertical" placeholder="{{ __('Printed on the quotation PDF…') }}">{{ old('memo', $quotation?->memo ?? '') }}</textarea>
                            <x-input-error :messages="$errors->get('memo')" />
                        </div>
                        <div class="q2-field">
                            <label for="notes_internal" class="q2-label">{{ __('Internal Notes') }}</label>
                            <textarea id="notes_internal" name="notes_internal" class="q2-input" style="height:auto;min-height:6rem;padding:.75rem .875rem;resize:vertical" placeholder="{{ __('Visible to your team only…') }}">{{ old('notes_internal', $quotation?->notes_internal ?? '') }}</textarea>
                        </div>
                    </div>
                </section>
            </div>

            {{-- §2/§3 rail: summary + quick nav --}}
            <aside class="q2-rail">
                <div class="q2-railcard">
                    <div class="q2-rail-group">{{ $isEdit ? __('Breakdown') : __('Summary') }}</div>
                    <div class="q2-railsum">
                        <div class="q2-srow"><span>{{ __('Customer') }}</span><span class="q2-sval" id="p-cust">{{ $selectedCustomer?->name ?? '—' }}</span></div>
                        <div class="q2-srow"><span>{{ __('Contact') }}</span><span class="q2-sval" id="p-contact">{{ $selectedCustomer?->display_name ?? $selectedCustomer?->name ?? '—' }}</span></div>
                        <div class="q2-srow"><span>{{ __('Date') }}</span><span class="q2-sval" id="p-date">{{ $quotation?->quotation_date?->format('M d, Y') ?? now()->format('M d, Y') }}</span></div>
                        <div class="q2-srow"><span>{{ __('Valid Until') }}</span><span class="q2-sval" id="p-valid">{{ $quotation?->valid_until?->format('M d, Y') ?? '—' }}</span></div>
                        <div style="height:12px"></div>
                        <div class="q2-srow"><span>{{ __('Subtotal') }}</span><span class="q2-sval" id="v-sub">0.00</span></div>
                        <div class="q2-srow" id="r-disc" style="display:none"><span>{{ __('Discount') }}</span><span class="q2-sval" id="v-disc">0.00</span></div>
                        <div class="q2-srow" id="r-tax" style="display:none"><span>{{ __('Tax') }}</span><span class="q2-sval" id="v-tax">0.00</span></div>
                        <div class="q2-srow gt"><span>{{ __('Grand Total') }}</span><span class="q2-sval" id="v-gt">{{ $cs }}0.00</span></div>
                        <div class="q2-sumnote" id="v-complete"></div>
                        <div id="p-lines"></div>
                        <p id="p-foot" class="q2-rail-memo" hidden></p>
                    </div>
                </div>

                <div class="q2-railcard">
                    <div class="q2-rail-group">{{ __('Quick Nav') }}</div>
                    @if ($isEdit)
                        @if (in_array($quotation->status, ['sent', 'accepted'], true))
                            <form method="POST" action="{{ route('accounting.quotations.convert-to-invoice', $quotation) }}" class="q2-vitem">
                                @csrf
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12M6 9l6-6 6 6M4 21h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span>{{ __('Convert to Invoice') }}</span>
                            </form>
                        @endif
                        <a href="{{ route('accounting.quotations.print', $quotation) }}" target="_blank" rel="noopener" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ __('Print / PDF') }}</span>
                        </a>
                        @if ($quotation?->customer?->email)
                            <form method="POST" action="{{ route('accounting.quotations.email', $quotation) }}" class="q2-vitem">
                                @csrf
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm18 3-10 7L2 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span>{{ __('Email') }}</span>
                            </form>
                        @endif
                        <a href="{{ $cancelRoute }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ __('Back') }}</span>
                        </a>
                    @else
                        <a href="{{ route('accounting.quotations.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>{{ __('Quotations List') }}</span>
                        </a>
                        <a href="{{ route('accounting.customers.create') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 13v5m2.5-2.5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>{{ __('New Customer') }}</span>
                        </a>
                        <a href="{{ route('accounting.journal-entries.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 3v18M4 5h10a3 3 0 0 1 3 3v12M4 5a2 2 0 0 0 2 2h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ __('Day Book') }}</span>
                        </a>
                    @endif
                </div>
            </aside>
        </div>
    </form>

    @if($isEdit && $quotation->created_by && (int) $quotation->created_by !== (int) auth()->id())
    <form id="quotation-delete-form" method="POST" action="{{ route('accounting.quotations.destroy', $quotation) }}" onsubmit="return fbConfirmSubmit(event, 'Delete this draft quotation? This cannot be undone.', { type: 'danger' })">
        @csrf
        @method('DELETE')
    </form>
    @endif

    {{-- §3.7 / §4.3 submit-choice modal --}}
    <div class="q2-scrim" id="quot-submit-scrim" hidden>
        <div class="q2-mbox q2-mbox--narrow" role="dialog" aria-modal="true" aria-labelledby="quot-submit-title">
            <div class="q2-mhead">
                <span class="q2-mic">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="q2-mtitle" id="quot-submit-title">{{ __('Submit quotation') }}</span>
                <button type="button" class="q2-mclose" aria-label="{{ __('Close') }}" onclick="quotCloseSubmit()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="q2-mbody">
                <div class="q2-optcards">
                    <button type="button" class="q2-optcard" onclick="quotSubmitChoice('finalize_later')">
                        <span class="q2-ocic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3zM4 7v10c0 1.7 3.6 3 8 3s8-1.3 8-3V7M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="q2-oct">{{ __('Save & send later') }}</span>
                    </button>
                    <button type="button" class="q2-optcard q2-optcard--final" onclick="quotSubmitChoice('finalize_print')">
                        <span class="q2-ocic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="q2-oct">{{ __('Finalize & print') }}</span>
                    </button>
                    <button type="button" class="q2-optcard q2-optcard--send q2-hintable q2-hint-right" id="quot-send-card" onclick="quotSubmitChoice('finalize_send')">
                        <span class="q2-ocic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm18 3-10 7L2 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="q2-oct">{{ __('Finalize & send to customer') }}</span>
                        <span class="q2-tip">{{ __('Provide customer email to activate this button') }}</span>
                    </button>
                </div>
            </div>
            <div class="q2-mfoot">
                <span class="q2-mnote">{{ __('Use Save Draft in the header for a quick save without submitting.') }}</span>
                <button type="button" class="q2-btn q2-btn--ghost" onclick="quotCloseSubmit()">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
    const QUOT_CS = @json($cs);
    const QUOT_DEFAULT_INCOME_ACCOUNT_ID = @json((string) $defaultIncomeAccountId);
    const QUOT_LINES = @json($linesData);
    let quotCustomerName = @json($selectedCustomer?->name ?? '');

    const fmt = n => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const parse = s => parseFloat(String(s == null ? '' : s).replace(/,/g, '')) || 0;
    const esc = s => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    function fmtDate(v) {
        if (!v) return '—';
        const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return v;
        const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    let lineIndex = 0;
    let quotCompleteCount = 0;

    function quotLineRow(data) {
        const d = data || {};
        const idx = lineIndex++;
        const gross = parse(d.quantity) * parse(d.unit_price);
        const flat = parse(d.discount);
        const pct = d.discount_pct != null
            ? parse(d.discount_pct)
            : (gross > 0 ? Math.round(flat / gross * 10000) / 100 : 0);
        const taxRate = parse(d.tax_rate);
        const incomeAccountId = d.income_account_id || QUOT_DEFAULT_INCOME_ACCOUNT_ID || '';

        return `
            <tr class="quot-row">
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="text" class="bill-ci bill-sku" value="${esc(d.sku || '')}" placeholder="Code" readonly tabindex="-1" aria-label="Item code" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    ${scopedSearchFieldHtml({
                        name: `lines[${idx}][product_id]`,
                        entity: 'product',
                        searchUrl: PRODUCT_SEARCH_URL,
                        value: d.product_id || '',
                        label: d.label || '',
                        placeholder: 'Search items…',
                    })}
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="text" class="bill-ci" name="lines[${idx}][description]" value="${esc(d.description || '')}" placeholder="Description" aria-label="Description" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="any" min="0" class="bill-ci bill-ci-num bill-qty" name="lines[${idx}][quantity]" value="${d.quantity != null ? d.quantity : 1}" aria-label="Quantity" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="0.01" min="0" class="bill-ci bill-ci-num bill-price" name="lines[${idx}][unit_price]" value="${d.unit_price != null ? d.unit_price : 0}" aria-label="Unit price" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="0.01" min="0" max="100" class="bill-ci bill-ci-num bill-disc-pct" value="${pct}" aria-label="Discount percent" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <span class="bill-amt bill-line-total q-amt">0.00</span>
                    <span class="q2-lineok" hidden aria-hidden="true">✓</span>
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <div class="flex gap-1 justify-end">
                        <button type="button" class="bill-ibtn q-ibtn" title="Duplicate line" aria-label="Duplicate line" onclick="quotDuplicateRow(this)">⧉</button>
                        <button type="button" class="bill-ibtn bill-ibtn--del q-ibtn q-ibtn--del" title="Delete line" aria-label="Delete line" onclick="quotRemoveLine(this)">🗑</button>
                    </div>
                </td>
                <input type="hidden" name="lines[${idx}][tax_rate]" class="quot-tax-rate" value="${taxRate}" />
                <input type="hidden" name="lines[${idx}][discount]" class="quot-flat-discount" value="${flat}" />
                <input type="hidden" name="lines[${idx}][income_account_id]" class="quot-income-account" value="${incomeAccountId}" />
            </tr>
        `;
    }

    function quotRowData(row) {
        const g = sel => (row.querySelector(sel) || { value: '' }).value;
        const num = sel => parse(g(sel));
        const picker = row.querySelector('[name*="[product_id]"]');
        const labelEl = picker && picker.closest('[x-data]')
            ? picker.closest('[x-data]').querySelector('.scoped-search-field input')
            : null;
        const qty = num('.bill-qty');
        const price = num('.bill-price');
        const pct = num('.bill-disc-pct');

        return {
            product_id: g('[name*="[product_id]"]'),
            label: labelEl ? labelEl.value : '',
            sku: g('.bill-sku'),
            description: g('[name*="[description]"]'),
            quantity: qty,
            unit_price: price,
            discount: qty * price > 0 ? Math.round(qty * price * pct) / 100 : 0,
            discount_pct: pct,
            tax_rate: num('.quot-tax-rate'),
            income_account_id: g('.quot-income-account'),
        };
    }

    function quotAddLine(data) {
        document.getElementById('quot-lines-body')
            .insertAdjacentHTML('beforeend', quotLineRow(data || {}));
        quotUpdateTotals();
    }

    function quotRemoveLine(btn) {
        const row = btn.closest('tr.quot-row');
        row.remove();
        if (!document.querySelector('#quot-lines-body tr.quot-row')) quotAddLine();
        quotUpdateTotals();
    }

    function quotDuplicateRow(btn) {
        const row = btn.closest('tr.quot-row');
        row.insertAdjacentHTML('afterend', quotLineRow(quotRowData(row)));
        quotUpdateTotals();
    }

    function quotUpdateTotals() {
        let subtotal = 0;
        let discount = 0;
        let tax = 0;
        const lines = [];

        let complete = 0;
        document.querySelectorAll('#quot-lines-body tr.quot-row').forEach(row => {
            const qty = parse(row.querySelector('.bill-qty').value);
            const price = parse(row.querySelector('.bill-price').value);
            const pct = parse(row.querySelector('.bill-disc-pct').value);
            const rate = parse(row.querySelector('.quot-tax-rate').value);
            const sku = (row.querySelector('.bill-sku').value || '').trim();
            const name = ((row.querySelector('.scoped-search-field input') || { value: '' }).value || '').trim();
            const desc = ((row.querySelector('[name*="[description]"]') || { value: '' }).value || '').trim();

            const gross = qty * price;
            const flat = gross > 0 ? Math.round(gross * pct) / 100 : 0;
            row.querySelector('.quot-flat-discount').value = flat.toFixed(2);

            const amount = gross - flat;
            const lineTax = amount * rate / 100;
            row.querySelector('.bill-line-total').textContent = fmt(amount + lineTax);

            const ok = !!(sku && name && desc && qty > 0 && price > 0 && amount > 0);
            const okEl = row.querySelector('.q2-lineok');
            if (okEl) okEl.hidden = !ok;
            if (ok) complete++;

            subtotal += gross;
            discount += flat;
            tax += lineTax;

            if (gross || amount) {
                lines.push({
                    c: sku,
                    n: name,
                    q: qty,
                    a: amount,
                });
            }
        });
        quotCompleteCount = complete;

        const grand = subtotal - discount + tax;

        document.getElementById('v-sub').textContent = fmt(subtotal);
        const rd = document.getElementById('r-disc');
        rd.style.display = discount > 0 ? '' : 'none';
        document.getElementById('v-disc').textContent = fmt(discount);
        const rt = document.getElementById('r-tax');
        rt.style.display = tax > 0 ? '' : 'none';
        document.getElementById('v-tax').textContent = fmt(tax);
        document.getElementById('v-gt').textContent = QUOT_CS + fmt(grand);

        const holder = document.getElementById('p-lines');
        holder.innerHTML = lines.map(l =>
            `<div class="quot-p-line"><span class="quot-p-code">${esc(l.c) || '—'}</span><span class="quot-p-name">${esc(l.n) || 'Untitled'}</span><span class="quot-p-qty">×${l.q}</span><span class="quot-p-amt">${fmt(l.a)}</span></div>`
        ).join('') || '<div class="quot-p-line"><span class="quot-p-name" style="color:#94a3b8">No line items yet</span></div>';

        quotEvaluateSubmit();
    }

    function quotSync() {
        document.getElementById('p-cust').textContent = quotCustomerName || '—';
        document.getElementById('p-contact').textContent = document.getElementById('quot-contact').value || '—';
        document.getElementById('p-date').textContent = fmtDate(document.getElementById('quotation_date').value);
        document.getElementById('p-valid').textContent = fmtDate(document.getElementById('valid_until').value);
        document.getElementById('p-foot').textContent = document.getElementById('memo').value || '—';
        quotUpdateTotals();
    }

    function quotTermsKey(raw) {
        const v = String(raw || '').toLowerCase().replace(/\s+/g, '');
        if (v.includes('receipt') || v.includes('immediate')) return 'receipt';
        if (v.includes('net7') || v.includes('7')) return 'net7';
        if (v.includes('net15') || v.includes('15')) return 'net15';
        if (v.includes('net60') || v.includes('60')) return 'net60';
        if (v.includes('net30') || v.includes('30')) return 'net30';
        return '';
    }

    function quotCustomerSelected(id, item) {
        if (item) {
            document.getElementById('quot-contact').value = item.display_name || item.label || '';
            document.getElementById('quot-email').value = item.email || '';
            document.getElementById('quot-phone').value = item.phone || '';
            const terms = document.getElementById('quot-terms');
            if (terms) {
                const key = quotTermsKey(item.payment_terms);
                if (key) terms.value = key;
            }
            quotCustomerName = item.label || '';
        }
        quotSync();
        quotEvaluateSubmit();
    }

    document.getElementById('quot-add-line').addEventListener('click', () => quotAddLine());

    const quotLinesBody = document.getElementById('quot-lines-body');
    quotLinesBody.addEventListener('item-selected', e => {
        const row = e.target.closest('tr.quot-row');
        if (!row || !row.querySelector('[name*="[product_id]"]')) return;
        const item = e.detail.item || {};

        if (item.sku != null) {
            const sku = row.querySelector('.bill-sku');
            if (sku) sku.value = item.sku;
        }
        if (item.description) {
            const desc = row.querySelector('[name*="[description]"]');
            if (desc) desc.value = item.description;
        }
        if (item.sales_price != null) {
            const price = row.querySelector('.bill-price');
            if (price) price.value = parse(item.sales_price).toFixed(2);
        }
        if (item.tax_rate != null) {
            const rate = row.querySelector('.quot-tax-rate');
            if (rate) rate.value = item.tax_rate;
        }
        const acct = row.querySelector('.quot-income-account');
        if (acct) acct.value = item.income_account_id || QUOT_DEFAULT_INCOME_ACCOUNT_ID || '';

        quotUpdateTotals();
    });

    quotLinesBody.addEventListener('input', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) quotUpdateTotals();
    });
    quotLinesBody.addEventListener('change', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) quotUpdateTotals();
    });

    ['quotation_date', 'valid_until', 'memo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', quotSync);
    });

    document.getElementById('quotation-form').addEventListener('submit', quotUpdateTotals);

    (QUOT_LINES.length ? QUOT_LINES : [{}]).forEach(d => quotAddLine(d));
    quotSync();

    /* ── customer mode: existing vs walk-in (R8) ────────────── */
    function quotSetMode(mode) {
        const walkin = mode === 'walkin';
        const searchWrap = document.getElementById('ci-search-wrap');
        const nameWrap = document.getElementById('ci-name-wrap');
        const saveNote = document.getElementById('ci-save-note');
        const searchInput = searchWrap ? searchWrap.querySelector('input[type="hidden"]') : null;
        const nameInput = document.getElementById('ci-name');

        if (searchWrap) searchWrap.style.display = walkin ? 'none' : '';
        if (nameWrap) nameWrap.style.display = walkin ? '' : 'none';
        if (saveNote) saveNote.style.display = walkin ? '' : 'none';
        if (searchInput) searchInput.disabled = walkin;
        if (nameInput) nameInput.disabled = !walkin;

        ['quot-contact', 'quot-email', 'quot-phone', 'quot-terms'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if (walkin) {
                el.classList.remove('q2-ro');
                el.removeAttribute('readonly');
                el.style.pointerEvents = '';
            } else if (el.tagName === 'SELECT') {
                el.classList.add('q2-ro');
                el.style.pointerEvents = 'none';
            } else {
                el.classList.add('q2-ro');
                el.setAttribute('readonly', 'readonly');
            }
        });
        quotEvaluateSubmit();
    }

    function quotEmailValid() {
        const el = document.getElementById('quot-email');
        const v = ((el && el.value) || '').trim();
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    }

    function quotEvaluateSubmit() {
        const modeEl = document.getElementById('ci-mode');
        const mode = modeEl ? modeEl.value : 'existing';
        const nameInput = document.getElementById('ci-name');
        const searchInput = document.querySelector('#ci-search-wrap input[type="hidden"]');
        const customerOk = mode === 'walkin'
            ? !!(nameInput && nameInput.value.trim())
            : !!(searchInput && searchInput.value);
        const contactOk = !!((document.getElementById('quot-contact') || {}).value || '').trim();

        const btn = document.getElementById('quot-submit-btn');
        if (btn) btn.disabled = !(customerOk && contactOk && quotCompleteCount >= 1);

        const sendCard = document.getElementById('quot-send-card');
        if (sendCard) {
            const ok = quotEmailValid();
            sendCard.classList.toggle('is-gated', !ok);
            sendCard.setAttribute('aria-disabled', ok ? 'false' : 'true');
        }

        const note = document.getElementById('v-complete');
        if (note) {
            note.textContent = quotCompleteCount >= 1
                ? `${quotCompleteCount} complete line${quotCompleteCount === 1 ? '' : 's'}`
                : 'Add at least one complete line to submit';
        }
    }

    /* ── submit-choice modal (R1/R2/R3/R11) ─────────────────── */
    function quotOpenSubmit() {
        const btn = document.getElementById('quot-submit-btn');
        if (btn && btn.disabled) return;
        const scrim = document.getElementById('quot-submit-scrim');
        if (scrim) { scrim.hidden = false; scrim.classList.add('is-open'); }
        document.body.classList.add('q2-modal-open');
        const first = scrim && scrim.querySelector('.q2-optcard');
        if (first) first.focus();
    }

    function quotCloseSubmit() {
        const scrim = document.getElementById('quot-submit-scrim');
        if (scrim) { scrim.classList.remove('is-open'); scrim.hidden = true; }
        document.body.classList.remove('q2-modal-open');
    }

    function quotSubmitChoice(mode) {
        if (mode === 'finalize_send' && !quotEmailValid()) {
            if (window.CB && CB.toast) {
                CB.toast('warning', @json(__('Email required')), @json(__('Provide customer email to activate this button.')));
            }
            return;
        }
        const hidden = document.getElementById('quot-submission-mode');
        if (hidden) hidden.value = mode;
        quotCloseSubmit();
        document.getElementById('quotation-form').submit();
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            const scrim = document.getElementById('quot-submit-scrim');
            if (scrim && !scrim.hidden) quotCloseSubmit();
        }
    });

    const quotModeSel = document.getElementById('ci-mode');
    if (quotModeSel) quotModeSel.addEventListener('change', () => quotSetMode(quotModeSel.value));
    const quotSubmitBtn = document.getElementById('quot-submit-btn');
    if (quotSubmitBtn) quotSubmitBtn.addEventListener('click', quotOpenSubmit);
    ['quot-contact', 'quot-email', 'quot-phone', 'ci-name'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', quotEvaluateSubmit);
    });

    quotSetMode(quotModeSel ? quotModeSel.value : 'existing');
</script>
